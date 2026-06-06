<?php

namespace Tests\Concerns;

use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

trait SocialiteTestHelpers
{
    private function makeSocialiteUser(
        string $id,
        string $nickname,
        string $name,
        string $token
    ): SocialiteUserContract {
        return new SocialiteFakeUser($id, $nickname, $name, $token);
    }

    private function fakeSocialiteDriver(SocialiteUserContract $instagramUser): void
    {
        $provider = new SocialiteFakeProvider($instagramUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('instagram')
            ->andReturn($provider);
    }
}

class SocialiteFakeProvider
{
    public function __construct(private readonly SocialiteUserContract $instagramUser) {}

    public function user(): SocialiteUserContract
    {
        return $this->instagramUser;
    }
}

class SocialiteFakeUser implements SocialiteUserContract
{
    public function __construct(
        private readonly string $id,
        private readonly string $nickname,
        private readonly string $name,
        public string $token
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return null;
    }

    public function getAvatar(): ?string
    {
        return null;
    }
}
