<?php

namespace Tests\Fakes;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FakeLogger implements LoggerInterface
{
    /** @var array<int, array{level: string, message: string}> */
    public array $entries = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::EMERGENCY, 'message' => (string) $message];
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::ALERT, 'message' => (string) $message];
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::CRITICAL, 'message' => (string) $message];
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::ERROR, 'message' => (string) $message];
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::WARNING, 'message' => (string) $message];
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::NOTICE, 'message' => (string) $message];
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::INFO, 'message' => (string) $message];
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::DEBUG, 'message' => (string) $message];
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => (string) $level, 'message' => (string) $message];
    }
}
