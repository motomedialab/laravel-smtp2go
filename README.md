# Laravel SMTP2Go Mail Transport Driver

[![Latest Version on Packagist](https://img.shields.io/packagist/v/motomedialab/smtp2go.svg?style=flat-square)](https://packagist.org/packages/motomedialab/smtp2go)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/motomedialab/smtp2go/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/motomedialab/smtp2go/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/motomedialab/smtp2go/code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/motomedialab/smtp2go/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/motomedialab/smtp2go.svg?style=flat-square)](https://packagist.org/packages/motomedialab/smtp2go)

Integrate SMTP2Go directly into your application using SMTP2Go's API

### Installation

```bash
composer require motomedialab/laravel-smtp2go
```

### Configuration

Within `config/mail.php`, add SMTP2Go into the `mailers` array:

```php
'smtp2go' => [
    'transport' => 'smtp2go',
    'api_key' => env('SMTP2GO_API_KEY'),
],
```

You need to set your SMTP2Go API Key within your environment file. If you don't yet have an API key, you can register one within your account, [here](https://app.smtp2go.com/sending/apikeys/).
You only need to grant this API Key Emails `/emails/send` API access.

```env
SMTP2GO_API_KEY=XXXXXXXX
```

### Usage

To use this as the main driver, i.e. all email will be routed via SMTP2Go by default, set
SMTP2Go to be the default mail driver in the environment file:

```env
MAIL_MAILER=smtp2go
```

If you want to use it on a case by case basis, you can call the driver directly, as below:

```php
Mail::driver('smtp2go')->send(...)
```


That's it, you're good to go!
