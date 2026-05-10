<?php

namespace Tests\Fakes;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FakeLogger implements LoggerInterface
{
    /** @var array<int, array{level: string, message: string, context: array<mixed>}> */
    public array $entries = [];

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::EMERGENCY, 'message' => (string) $message, 'context' => $context];
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::ALERT, 'message' => (string) $message, 'context' => $context];
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::CRITICAL, 'message' => (string) $message, 'context' => $context];
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::ERROR, 'message' => (string) $message, 'context' => $context];
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::WARNING, 'message' => (string) $message, 'context' => $context];
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::NOTICE, 'message' => (string) $message, 'context' => $context];
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::INFO, 'message' => (string) $message, 'context' => $context];
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => LogLevel::DEBUG, 'message' => (string) $message, 'context' => $context];
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }
}
