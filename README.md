# Laravel SMTP2Go Mail Transport Driver

Integrate SMTP2Go directly into your application using SMTP2Go's API

### Installation

```bash
composer require motomedialab/smtp2go
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
