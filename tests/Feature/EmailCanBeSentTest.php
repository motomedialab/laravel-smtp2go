<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Motomedialab\Smtp2Go\Exceptions\Smtp2GoException;
use Tests\TestCase;

class EmailCanBeSentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mail.from', [
            'address' => 'test@test.com',
            'name' => 'Testing!',
        ]);

        Config::set('mail.mailers.smtp2go', [
            'transport' => 'smtp2go',
            'api_key' => 'test_key',
        ]);
    }

    public function test_smtp2go_mail_driver_makes_request_to_api()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'succeeded' => [
                        'test@test.com'
                    ]
                ]
            ])
        ]);

        Mail::driver('smtp2go')->raw('Testing', function (Message $message) {
            $message->to('test@test.com')->subject('test');
        });

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.smtp2go.com/v3/email/send'
            && $request->hasHeader('X-Smtp2go-Api-Key', 'test_key')
            && $request['to'] === ['test@test.com']
            && $request['sender'] === 'Testing! <test@test.com>'
            && $request['subject'] === 'test'
            && $request['text_body'] === 'Testing');
    }

    public function test_smtp2go_email_id_is_recorded_on_the_sent_message()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'succeeded' => [
                        'test@test.com'
                    ],
                    'email_id' => '1abcde-fghij-2klmno',
                ]
            ])
        ]);

        $sent = Mail::driver('smtp2go')->raw('Testing', function (Message $message) {
            $message->to('test@test.com')->subject('test');
        });

        $this->assertSame('1abcde-fghij-2klmno', $sent->getMessageId());
    }

    public function test_smtp2go_message_id_is_left_alone_when_the_api_returns_no_email_id()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'succeeded' => [
                        'test@test.com'
                    ]
                ]
            ])
        ]);

        $sent = Mail::driver('smtp2go')->raw('Testing', function (Message $message) {
            $message->to('test@test.com')->subject('test');
            $message->getSymfonyMessage()->getHeaders()->addIdHeader('Message-ID', 'control@test.com');
        });

        $this->assertSame('control@test.com', $sent->getMessageId());
    }

    public function test_smtp2go_email_id_is_available_on_the_message_sent_event()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'succeeded' => [
                        'test@test.com'
                    ],
                    'email_id' => '1abcde-fghij-2klmno',
                ]
            ])
        ]);

        $messageId = null;

        Event::listen(function (MessageSent $event) use (&$messageId) {
            $messageId = $event->sent->getMessageId();
        });

        Mail::driver('smtp2go')->raw('Testing', function (Message $message) {
            $message->to('test@test.com')->subject('test');
        });

        $this->assertSame('1abcde-fghij-2klmno', $messageId);
    }

    public function test_smtp2go_api_key_is_sent_in_a_header_rather_than_the_body()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'succeeded' => [
                        'test@test.com'
                    ]
                ]
            ])
        ]);

        Mail::driver('smtp2go')->raw('Testing', function (Message $message) {
            $message->to('test@test.com')->subject('test');
        });

        Http::assertSent(fn (Request $request) => $request->hasHeader('X-Smtp2go-Api-Key', 'test_key')
            && ! array_key_exists('api_key', $request->data()));
    }

    public function test_smtp2go_failure_context_excludes_the_api_key_and_message_content()
    {
        Http::fake([
            '*' => Http::response($this->errorResponse(), 400)
        ]);

        $context = json_encode($this->sendFailingMessage()->context());

        Http::assertSent(fn (Request $request) => $request['text_body'] === 'Secret text body'
            && $request['html_body'] === '<p>Secret html body</p>'
            && str_contains($request['attachments'][0]['fileblob'], base64_encode('Secret attachment')));

        $this->assertStringNotContainsString('test_key', $context);
        $this->assertStringNotContainsString('Secret text body', $context);
        $this->assertStringNotContainsString('Secret html body', $context);
        $this->assertStringNotContainsString('Secret attachment', $context);
        $this->assertStringNotContainsString(base64_encode('Secret attachment'), $context);
        $this->assertStringNotContainsString('customer@example.com', $context);
    }

    public function test_smtp2go_failure_context_keeps_what_is_needed_to_debug_the_failure()
    {
        Http::fake([
            '*' => Http::response($this->errorResponse(), 400)
        ]);

        $exception = $this->sendFailingMessage();
        $sent = Http::recorded()->first()[0];

        $this->assertSame(400, $exception->getCode());
        $this->assertSame([
            'status' => 400,
            'data' => [
                'sender' => 'Testing! <test@test.com>',
                'subject' => 'test',
                'recipients' => [
                    'to' => 2,
                    'cc' => 1,
                    'bcc' => 0,
                ],
                'attachments' => [
                    [
                        'filename' => 'invoice.pdf',
                        'mimetype' => $sent['attachments'][0]['mimetype'],
                        'size' => 17,
                    ],
                ],
            ],
            'error' => $this->errorResponse(),
        ], $exception->context());
    }

    protected function sendFailingMessage(): Smtp2GoException
    {
        try {
            Mail::driver('smtp2go')->raw('Secret text body', function (Message $message) {
                $message->to(['customer@example.com', 'another@example.com'])
                    ->cc('manager@example.com')
                    ->subject('test');
                $message->html('<p>Secret html body</p>');
                $message->attachData('Secret attachment', 'invoice.pdf', ['mime' => 'application/pdf']);
            });
        } catch (Smtp2GoException $exception) {
            return $exception;
        }

        $this->fail('Expected the send to throw a Smtp2GoException.');
    }

    protected function errorResponse(): array
    {
        return [
            'request_id' => '22e5acba-43bf-11e6-ae42-408d5cce2644',
            'data' => [
                'error_code' => 'E_ApiResponseCodes.ENDPOINT_PERMISSION_DENIED',
                'error' => 'You do not have permission to access this API endpoint',
            ],
        ];
    }
}
