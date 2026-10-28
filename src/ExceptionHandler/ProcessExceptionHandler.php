<?php

declare(strict_types=1);

namespace SuperKernel\Framework\ExceptionHandler;

use SuperKernel\Contract\ExceptionDispatcherInterface;
use Throwable;

use function set_exception_handler;

final class ProcessExceptionHandler
{
    private bool $registered = false;

    public function __construct(private readonly ExceptionDispatcherInterface $exceptionDispatcher)
    {
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        set_exception_handler($this);
        $this->registered = true;
    }

    public function __invoke(Throwable $throwable): never
    {
        $this->handle($throwable);

        exit(1);
    }

    public function handle(Throwable $throwable): void
    {
        $this->exceptionDispatcher->dispatch($throwable);
    }
}
