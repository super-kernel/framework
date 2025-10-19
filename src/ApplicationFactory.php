<?php
declare (strict_types=1);

namespace SuperKernel\Framework;

use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Event\BootApplication;

#[
    Provider(ApplicationInterface::class),
    Factory,
]
final readonly class ApplicationFactory
{
    public function __invoke(ApplicationInterface $application, EventDispatcherInterface $eventDispatcher): ApplicationInterface
    {
        $eventDispatcher->dispatch(new BootApplication());

        return $application;
    }
}