<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Listener;

use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ListenerInterface;
use SuperKernel\Event\BeforeApplicationStart;
use SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler;

use const PHP_INT_MAX;

#[Listener(PHP_INT_MAX)]
final readonly class BeforeApplicationStartListener implements ListenerInterface
{
    public function __construct(private ProcessExceptionHandler $exceptionHandler)
    {
    }

    public function listen(): array
    {
        return [
            BeforeApplicationStart::class,
        ];
    }

    public function process(object $event): void
    {
        $this->exceptionHandler->register();
    }
}
