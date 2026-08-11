<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
            && $request['api_key'] === 'test_key'
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
}
