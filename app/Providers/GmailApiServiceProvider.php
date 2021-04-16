<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Service\GmailApiService;
use Illuminate\Http\Request;


class GmailApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(GmailApiService::class, function($app){
            return new GmailApiService($app->make(Request::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
