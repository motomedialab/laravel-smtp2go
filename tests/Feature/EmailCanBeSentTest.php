<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
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


    /**
     * @test
     */
    public function smtp2go_mail_driver_makes_request_to_api()
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


}
