<?php

declare(strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class Application extends SymfonyApplication implements ApplicationInterface
{
    public function __construct(private readonly ExceptionDispatcherInterface $exceptionDispatcher)
    {
        parent::__construct();

        $this->setCatchErrors();
    }

    public function renderThrowable(Throwable $e, OutputInterface $output): void
    {
        $this->exceptionDispatcher->dispatch($e);
    }
}
