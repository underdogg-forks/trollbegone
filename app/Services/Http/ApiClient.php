<?php

namespace App\Services\Http;

use App\Enums\RequestMethod;
use Illuminate\Http\Client\Response;

interface ApiClient
{
    public function request(
        RequestMethod|string $method,
        string $url,
        array $options = []
    ): Response;
}
