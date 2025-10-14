<?php

declare(strict_types=1);

namespace Gember\EventSourcing\Test\TestDoubles\Util\Log;

use Override;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * @internal
 */
final class TestLogger implements LoggerInterface
{
    /**
     * @var list<array{message: string, context: array<int|string, mixed>}>
     */
    public array $logs = [];

    #[Override]
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    #[Override]
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    #[Override]
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    #[Override]
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    #[Override]
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    #[Override]
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    #[Override]
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    #[Override]
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    #[Override]
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
