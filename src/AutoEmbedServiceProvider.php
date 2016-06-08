<?php

namespace Puz\Mail\AutoEmbed;

use Illuminate\Support\ServiceProvider;

class AutoEmbedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make('mailer')->getSwiftMailer()->registerPlugin(new ImagesToAttachments);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // TODO: Nothing
    }
}
