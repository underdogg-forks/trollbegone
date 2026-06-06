<?php

namespace App\Providers;

use App\Contracts\InstagramApiServiceContract;
use App\Services\Http\ApiClient;
use App\Services\Http\ExternalClient;
use App\Services\Http\HttpClientExceptionDecorator;
use App\Services\Http\RequestLoggerDecorator;
use App\Services\Instagram\BlockedAccountService;
use App\Services\Instagram\Endpoints\BlockUserEndpoint;
use App\Services\Instagram\Endpoints\DeleteCommentEndpoint;
use App\Services\Instagram\Endpoints\GetFollowingEndpoint;
use App\Services\Instagram\Endpoints\GetPostCommentsEndpoint;
use App\Services\Instagram\Endpoints\GetPostsByUsernameEndpoint;
use App\Services\Instagram\Endpoints\GetStoriesEndpoint;
use App\Services\Instagram\Endpoints\GetStoryCommentsEndpoint;
use App\Services\Instagram\Endpoints\GetUserInfoEndpoint;
use App\Services\Instagram\InstagramApiClient;
use App\Services\Instagram\InstagramApiService;
use Illuminate\Support\ServiceProvider;

class ApiClientServiceProvider extends ServiceProvider
{
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

        $this->app->singleton(InstagramApiClient::class, function ($app) {
            return new InstagramApiClient($app->make(ApiClient::class));
        });

        $this->app->singleton(InstagramApiService::class, function ($app) {
            $getUserInfo = $app->make(GetUserInfoEndpoint::class);

            return new InstagramApiService(
                $app->make(GetFollowingEndpoint::class),
                $app->make(GetStoriesEndpoint::class),
                $app->make(GetStoryCommentsEndpoint::class),
                $app->make(GetPostCommentsEndpoint::class),
                new GetPostsByUsernameEndpoint($app->make(InstagramApiClient::class), $getUserInfo),
                $getUserInfo,
                $app->make(BlockUserEndpoint::class),
                $app->make(DeleteCommentEndpoint::class),
            );
        });

        $this->app->alias(InstagramApiService::class, InstagramApiServiceContract::class);

        $this->app->singleton(BlockedAccountService::class, function ($app) {
            return new BlockedAccountService($app->make(InstagramApiServiceContract::class));
        });
    }
}
