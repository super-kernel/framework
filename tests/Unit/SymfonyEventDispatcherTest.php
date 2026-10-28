<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use ReflectionClass;
use stdClass;
use SuperKernel\Attribute\Provider;
use SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SymfonyEventDispatcherTest extends TestCase
{
    public function testDeclaresTheSymfonyDispatcherProviderContract(): void
    {
        $dispatcher = new ReflectionClass(SymfonyEventDispatcher::class);
        $attributes = $dispatcher->getAttributes(Provider::class);
        $constructor = $dispatcher->getConstructor();

        self::assertTrue($dispatcher->isFinal());
        self::assertTrue($dispatcher->isReadOnly());
        self::assertTrue($dispatcher->implementsInterface(EventDispatcherInterface::class));
        self::assertCount(1, $attributes);
        self::assertSame(EventDispatcherInterface::class, $attributes[0]->newInstance()->className);
        self::assertNotNull($constructor);
        self::assertSame(1, $constructor->getNumberOfRequiredParameters());
        self::assertSame(
            PsrEventDispatcherInterface::class,
            (string) $constructor->getParameters()[0]->getType(),
        );
    }

    public function testDelegatesTheEventObjectAndIgnoresTheSymfonyEventName(): void
    {
        $psrDispatcher = new class() implements PsrEventDispatcherInterface {
            /** @var list<object> */
            public array $events = [];

            public function dispatch(object $event): object
            {
                $this->events[] = $event;

                return $event;
            }
        };
        $dispatcher = new SymfonyEventDispatcher($psrDispatcher);
        $event = new stdClass();

        $returned = $dispatcher->dispatch($event, 'legacy.string.name');

        self::assertSame($event, $returned);
        self::assertSame([
            $event,
        ], $psrDispatcher->events);
    }
}
