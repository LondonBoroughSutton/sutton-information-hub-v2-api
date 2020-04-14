<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(Client::class, function () {
            return new Client(
                config('hlp.twilio.sid'),
                config('hlp.twilio.token')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //
    }
}
