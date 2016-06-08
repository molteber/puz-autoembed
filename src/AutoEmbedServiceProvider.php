<?php

namespace Puz\Mail\AutoEmbed;

use Illuminate\Support\ServiceProvider;

class AutoEmbedServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('mailer')->getSwiftMailer()->registerPlugin(new ImagesToAttachments);
    }
}
