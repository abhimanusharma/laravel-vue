<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\GmailApiService;


class GmailApiServiceProvider extends ServiceProvider
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
        //

        $this->app->bind(GmailApiService::class, function($app){
            return new GmailApiService();
        });
    }
}
