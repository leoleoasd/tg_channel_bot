<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class TGBotProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $client = new Client([
            'proxy' => config('tgbot.proxy'),
        ]);
        \Telegram::getClient()->getHttpClientHandler()->setClient($client);
    }
}
