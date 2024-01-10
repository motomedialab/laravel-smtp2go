<?php

namespace Motomedialab\Smtp2Go\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Motomedialab\Smtp2Go\Transports\Smtp2GoTransport;

class Smtp2GoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // register the smtp2go driver
        Mail::extend(
            'smtp2go',
            fn (array $config = []) => app(Smtp2GoTransport::class)
        );
    }
}
