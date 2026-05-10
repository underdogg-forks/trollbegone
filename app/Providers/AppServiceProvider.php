<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Instagram Socialite provider
        Socialite::extend('instagram', function ($app) {
            $config = $app['config']['services.instagram'];

            return Socialite::buildProvider(InstagramProvider::class, $config);
        });
    }
}
