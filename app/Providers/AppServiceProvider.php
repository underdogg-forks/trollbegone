<?php

namespace App\Providers;

use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExternalClient::class);

        $this->app->singleton(HttpClientExceptionDecorator::class, function ($app) {
            return new HttpClientExceptionDecorator($app->make(ExternalClient::class));
        });

        $this->app->singleton(InstagramApiService::class, function ($app) {
            return new InstagramApiService($app->make(HttpClientExceptionDecorator::class));
        });

        $this->app->singleton(BlockedAccountService::class, function ($app) {
            return new BlockedAccountService($app->make(InstagramApiService::class));
        });
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
