<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Provider;

use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Event\BeforeApplicationStart;
use SuperKernel\Framework\Application;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

#[FactoryProvider(ApplicationInterface::class)]
final readonly class ApplicationProvider
{
    /** @throws Throwable When package lookup or a startup listener fails */
    public function __invoke(
        EventDispatcherInterface     $eventDispatcher,
        ExceptionDispatcherInterface $exceptionDispatcher,
        PackageManagerInterface      $packageManager,
    ): ApplicationInterface
    {
        $root = $packageManager->root();
        $application = new Application($exceptionDispatcher);
        $application->setName($root->name());
        $version = $root->version();

        if (null !== $version) {
            $application->setVersion($version);
        }

        $application->setDispatcher($eventDispatcher);
        $eventDispatcher->dispatch(new BeforeApplicationStart($application));

        return $application;
    }
}
