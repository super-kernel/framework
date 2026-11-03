<?php

declare(strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Exception\ExceptionInterface as ConsoleExceptionInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class Application extends SymfonyApplication implements ApplicationInterface
{
    public function __construct(private readonly ExceptionDispatcherInterface $exceptionDispatcher)
    {
        parent::__construct();

        $this->setCatchErrors();
    }

    /**
     * Renders Symfony Console exceptions, such as unknown commands and invalid input, as Console messages and
     * dispatches every other throwable through the shared exception dispatcher.
     */
    public function renderThrowable(Throwable $e, OutputInterface $output): void
    {
        if ($e instanceof ConsoleExceptionInterface) {
            parent::renderThrowable($e, $output);

            return;
        }

        $this->exceptionDispatcher->dispatch($e);
    }
}
