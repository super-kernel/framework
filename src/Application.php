<?php
declare(strict_types=1);

namespace SuperKernel\Framework;

use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Event\BootApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends \Symfony\Component\Console\Application implements ApplicationInterface
{
    private readonly EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($name, $version);
    }

    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $this->eventDispatcher->dispatch(new BootApplication());

        return parent::run($input, $output);
    }
}