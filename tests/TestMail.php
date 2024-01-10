<?php

namespace Tests;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

class TestMail extends Mailable
{
    public function content(): Content
    {
        return new Content(
            view: null,
            text: 'testing'
        );
    }
}
