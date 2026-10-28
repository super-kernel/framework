<?php

declare(strict_types=1);

namespace SuperKernel\Framework\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use SuperKernel\Attribute\Provider;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Adapts Symfony's dispatcher contract to the application's PSR-14 dispatcher.
 */
#[Provider(EventDispatcherInterface::class)]
final readonly class SymfonyEventDispatcher implements EventDispatcherInterface
{
    public function __construct(private PsrEventDispatcherInterface $dispatcher)
    {
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        return $this->dispatcher->dispatch($event);
    }
}
