# Puz - Auto embedder for images
*This package is mainly made for Laravel 5.2, but is fully usefull for every application which uses [Swift Mailer](https://packagist.org/packages/swiftmailer/swiftmailer)*

To install: `composer require puz/mail_autoembed`

To use without laravel:
```php
$mailer = new \Swift_Mailer;
$mailer->registerPlugin(new \Puz\Mail\AutoEmbed\ImagesToAttachments);
```

To use with laravel ^5.2:
```php
// Add the service provider in the list of your service providers in app.php. It MUST be added after laravels mail service provider
$providers[
    ...
    \Puz\Mail\AutoEmbed\AutoEmbedServiceProvider::class,
    ...
 ];
```

This package will register two plugins to the mailer.
1. beforeSendPerformed
    Right before the email is sent, it will scan the email for <img> tags and it's src attribute. It will accept data:image, local image path and ~~remote image~~ (soon)
2. sendPerformed
    Right after the mailer have sent the email away, it will go ahead and delete the temporary created images (for remote and data:image).

# Contributions
Any suggestion or code improvement will be gladly accepted.

# Future features
 - Save all the images which is attached. This can be usefull in situations where the user send the email from a texteditor and you need to display the email on your website as well. Good thing you saved the image right?!
