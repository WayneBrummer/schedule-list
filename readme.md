# Schedule List

Schedule List will provide a console based view of all schedules currently active in the application.

## Install

Via Composer

```bash
composer require waynebrummer/schedule-list
```

Publish the config file and migration

```bash
php artisan vendor:publish --provider="Pace\ScheduleList\Providers\ServiceProvider"
```

Run the migration

```bash
php artisan migrate
```

Note: If you would like to use a different connection to store your models,
you should update the mail-telemetry.php config entry `connection` before running the migrations.

## Usage

Once installed, all outgoing mail will be logged to the database.

The following config options are available in config/mail-telemetry.php:

- **name**: set your App Name.

- **pixel**: set to true to inject a tracking pixel into all outgoing html emails.
  
- **links**: set to true to rewrite all anchor href links to include a tracking link.
  The link will take the user back to your website which will then redirect them to the final destination after logging the click.

- **expire-days**: How long in days that an email should be retained in your database.
- If you are sending a lot of mail, you probably want it to eventually expire. Set it to zero to never purge old emails from the database.

- **route**: The route information for the tracking URLs. Set the prefix and middleware as desired.

- **auth-route**: The route information for the admin. Set the prefix and middleware.
  
- **date-format**: You can define the format to show dates in the Admin Panel.

If you do not wish to have an email tracked, then you can add the `X-No-Track` header to your message.
Put any random string into this header to prevent the tracking from occurring. 
The header will be removed from the email prior to being sent.

```php
\Mail::send('email.test', [], function ($message) {
    // ... other settings here
    $message->getHeaders()->addTextHeader('X-No-Track',Str::random(10));
});
```

## Note on local development testing

Several people have reporting the tracking pixel not working while they were testing.
What is happening with the tracking pixel is that the email client is connecting to your
website to log the view. In order for this to happen, images have to be visible in the client,
and the client has to be able to connect to your server.

When you are in a local dev environment (i.e. using the `.test` domain with Valet,
or another domain known only to your computer) you must have an email client on your computer.

Further complicating this is the fact that Gmail and some other web-based email
clients don't connect to the images directly, but instead connect via proxy.

That proxy won't have a connection to your `.test` domain and therefore will not properly track emails.
I always recommend using [mailtrap.io](https://mailtrap.io) for any development environment when you are sending emails.
Not only does this solve the issue (mailtrap.io does not use a proxy service to forward images in the emails) 
but it also protects you from accidentally sending real emails from your test environment.

## Events

When an email is sent, viewed, or a link is clicked, its tracking information is counted in the database using the Pace\MailTelemetry\Models\Email model.

You may want to do additional processing on these events, so an event is fired in these cases:

- Pace\MailTelemetry\Events\EmailEvent

If you are using the Amazon SNS notification system, an event is fired when you receive a permanent bounce. You may want to mark the email as bad or remove it from your database.

- Pace\MailTelemetry\Events\PermanentBouncedMessageEvent

To install an event listener, you will want to create a file like the following:

```bash
php artisan make:listener EmailViewed
```

```php
<?php

namespace App\Listeners;

use Pace\MailTelemetry\Events\EmaiEvent;

class EmailViewed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ViewEmailEvent  $event
     * @return void
     */
    public function handle(EmaiEvent $event)
    {
        // Access the model using $event->email...
    }
}
```

### Passing data to the event listeners

Often times you may need to link a sent email to another model.
The best way to handle this is to add a header to your outgoing email that you can retrieve in your event listener.

Here is an example:

```php
/**
 * Send an email and do processing on a model with the email
 */
\Mail::send('email.test', [], function ($message) use($email, $subject, $name, $model) {
    $message->from('from@johndoe.com', 'From Name');
    $message->sender('sender@johndoe.com', 'Sender Name');
    $message->to($email, $name);
    $message->subject($subject);

    // Create a custom header that we can later retrieve
    $message->getHeaders()->addTextHeader('X-Model-ID',$model->id);
});
```

and then in your event listener:

```
public function handle(EmailSentEvent $event)
{
    $tracker = $event->sent_email;
    $model_id = $event->sent_email->getHeader('X-Model-ID');
    $model = Model::find($model_id);
    // Perform your tracking/linking tasks on $model knowing the SentEmail object
}
```

Note that the headers you are attaching to the email are actually going out with the message, 
so do not store any data that you wouldn't want to expose to your email recipients.

## Exceptions

The following exceptions may be thrown. You may add them to your ignore list in your exception handler, or handle them as you wish.

- Pace\MailTelemetry\Exceptions\BadUrlLink.
- - Something went wrong with the url link.
  
  Either the base 64 encoded url is bad (this only applies to mail sent through version 2.1) or the email hash was not found to apply the link to.

## Amazon SES features

If you use Amazon SES, you can add some additional information to your tracking.
To set up the SES callbacks, first set up SES notifications under your domain in the SES control panel.
Then subscribe to the topic by going to the admin panel of the notification
topic and creating a subscription for the URL you copied from the admin page.
The system should immediately respond to the subscription request. If you like,
you can use multiple subscriptions (i.e. one for delivery, one for bounces). 

See above for events that are fired on a failed message. 
**For added security, it is recommended to set the topic ARN into the mail-telemetry config.**

## Admin Panel

WIP
is more of a authed route.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email @wayne.brummer instead of using the issue tracker.

## Credits

- [J David Baker][link-author] for the initial idea.

## License

The Apache License (Apache 2.0). Please see [License File](LICENSE.md) for more information.
