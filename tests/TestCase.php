<?php

namespace Tests;

use App\Services\Http\HttpClientExceptionDecorator;
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
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeHttpClient;

abstract class TestCase extends BaseTestCase
{
    protected function makeInstagramApiService(FakeHttpClient $fakeHttpClient): InstagramApiService
    {
        $client = new InstagramApiClient(new HttpClientExceptionDecorator($fakeHttpClient));
        $getUserInfo = new GetUserInfoEndpoint($client);

        return new InstagramApiService(
            new GetFollowingEndpoint($client),
            new GetStoriesEndpoint($client),
            new GetStoryCommentsEndpoint($client),
            new GetPostCommentsEndpoint($client),
            new GetPostsByUsernameEndpoint($client, $getUserInfo),
            $getUserInfo,
            new BlockUserEndpoint($client),
            new DeleteCommentEndpoint($client),
        );
    }
}
