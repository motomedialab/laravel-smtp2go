<?php

namespace Motomedialab\Smtp2Go\Transports;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Motomedialab\Smtp2Go\Exceptions\Smtp2GoException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\BaseTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class Smtp2GoTransport extends BaseTransport
{

    protected array $config;

    protected string $endpoint = 'https://api.smtp2go.com/v3/email/send';

    public function __construct(EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);

        $this->config = config('mail.mailers.smtp2go', []);
    }

    public function __toString(): string
    {
        return 'smtp2go';
    }

    protected function doSend(SentMessage $message): void
    {
        // retrieve our original email
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        // build our data to send to the API.
        $data = collect([
            'api_key' => config("mail.mailers.smtp2go.api_key"),
            'to' => $this->sanitiseAddresses($email->getTo())->all(),
            'cc' => $this->sanitiseAddresses($email->getCc())->all(),
            'bcc' => $this->sanitiseAddresses($email->getBcc())->all(),
            'sender' => $this->sanitiseAddresses($email->getFrom())->first(),
            'subject' => $email->getSubject(),
            'html_body' => $email->getHtmlBody(),
            'text_body' => $email->getTextBody(),
            'custom_headers' => collect([
                [
                    'header' => 'Reply-To',
                    'value' => $this->sanitiseAddresses($email->getReplyTo())->first(),
                ]
            ])->filter(fn ($value) => $value['value'])->all(),
            'attachments' => collect($email->getAttachments())->map(fn (DataPart $attachment) => [
                'filename' => $attachment->getFilename(),
                'fileblob' => $attachment->bodyToString(),
                'mimetype' => $attachment->getMediaType(),
            ])->all(),
        ])->filter()->all();

        $response = Http::timeout(60)->post($this->endpoint, $data);

        if (!$response->successful() || $response->json('data.succeeded') < 1) {
            throw Smtp2GoException::make('Failed to send via ' . $this . ' transport', $response->status())
                ->setContext(['data' => $data, 'error' => $response->json()]);
        }
    }

    /**
     * Sanitise addresses to meet the API format.
     */
    protected function sanitiseAddresses(...$addresses): Collection
    {
        return collect(Arr::flatten($addresses))->filter()
            ->map(fn (Address $address): string => $address->getName() ? sprintf(
                '%s <%s>',
                Str::of($address->getName())->replace(['<', '>'], ['\<', '\>']),
                $address->getAddress()
            ) : $address->getAddress());
    }
}
