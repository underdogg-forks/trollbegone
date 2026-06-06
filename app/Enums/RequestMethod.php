<?php

namespace App\Enums;

/**
 * HTTP Request Method Enum
 *
 * Represents standard HTTP request methods used throughout the application.
 */
enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';

    public static function normalize(self|string $method): string
    {
        return $method instanceof self ? $method->value : (string) $method;
    }
}
