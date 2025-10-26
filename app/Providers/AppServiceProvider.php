<?php

namespace App\Providers;

use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Models\InstagramAccount;
use App\Policies\InstagramAccountPolicy;
use App\Services\Instagram\InstagramApiService;
use App\Services\Instagram\BlockedAccountService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(InstagramAccount::class, InstagramAccountPolicy::class);
    }
}
