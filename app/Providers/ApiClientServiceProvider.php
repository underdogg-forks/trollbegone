<?php

namespace App\Providers;

use App\Services\Http\ApiClient;
use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Http\RequestLoggerDecorator;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\ServiceProvider;

class ApiClientServiceProvider extends ServiceProvider
{
    /**
     * Register API and Instagram service bindings.
     */
    public function register(): void
    {
        $this->app->singleton(ExternalClient::class);

        $this->app->singleton(ApiClient::class, function ($app) {
            return new RequestLoggerDecorator(
                new HttpClientExceptionDecorator(
                    $app->make(ExternalClient::class)
                )
            );
        });

        $this->app->singleton(InstagramApiService::class, function ($app) {
            return new InstagramApiService($app->make(ApiClient::class));
        });

        $this->app->singleton(BlockedAccountService::class, function ($app) {
            return new BlockedAccountService($app->make(InstagramApiService::class));
        });
    }
}
