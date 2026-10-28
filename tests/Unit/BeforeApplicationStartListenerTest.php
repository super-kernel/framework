<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use SuperKernel\Attribute\Listener;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Contract\ListenerInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\PackageMetadataInterface;
use SuperKernel\Event\BeforeApplicationStart;
use SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher;
use SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler;
use SuperKernel\Framework\Listener\BeforeApplicationStartListener;
use SuperKernel\Framework\Provider\ApplicationProvider;
use SuperKernel\Framework\Tests\Fixture\RecordingEventDispatcher;
use Throwable;

use function restore_exception_handler;
use function set_exception_handler;

use const PHP_INT_MAX;

final class BeforeApplicationStartListenerTest extends TestCase
{
    public function testConstructionIsPassiveAndDeclaresEarlyStartupRegistration(): void
    {
        $sentinel = static function (Throwable $throwable): void {
        };
        set_exception_handler($sentinel);

        try {
            $listener = new BeforeApplicationStartListener(new ProcessExceptionHandler(
                $this->createStub(ExceptionDispatcherInterface::class),
            ));
            $reflection = new ReflectionClass($listener);
            $attributes = $reflection->getAttributes(Listener::class);

            self::assertTrue($reflection->implementsInterface(ListenerInterface::class));
            self::assertTrue($reflection->isFinal());
            self::assertTrue($reflection->isReadOnly());
            self::assertCount(1, $attributes);
            self::assertSame(PHP_INT_MAX, $attributes[0]->newInstance()->priority);
            self::assertSame([
                BeforeApplicationStart::class,
            ], $listener->listen());
            self::assertSame($sentinel, self::currentHandler());
        } finally {
            restore_exception_handler();
        }
    }

    /** @throws Throwable when application startup unexpectedly fails */
    public function testApplicationStartupRegistersTheInjectedHandler(): void
    {
        $failure = new RuntimeException('Failure after application creation.');
        $exceptionDispatcher = $this->createMock(ExceptionDispatcherInterface::class);
        $exceptionDispatcher->expects(self::once())->method('dispatch')->with(self::identicalTo($failure));
        $handler = new ProcessExceptionHandler($exceptionDispatcher);
        $listener = new BeforeApplicationStartListener($handler);
        $events = new RecordingEventDispatcher($listener->process(...));

        try {
            $application = new ApplicationProvider()(
                new SymfonyEventDispatcher($events),
                $exceptionDispatcher,
                $this->packages(),
            );

            self::assertCount(1, $events->events);
            $event = $events->events[0];
            self::assertInstanceOf(BeforeApplicationStart::class, $event);
            self::assertSame($application, $event->application());
            self::assertSame($handler, self::currentHandler());
            $handler->handle($failure);
        } finally {
            restore_exception_handler();
        }
    }

    /** @throws Throwable when application startup unexpectedly fails */
    public function testRepeatedStartupDoesNotReplaceALaterProcessHandler(): void
    {
        $exceptionDispatcher = $this->createStub(ExceptionDispatcherInterface::class);
        $handler = new ProcessExceptionHandler($exceptionDispatcher);
        $listener = new BeforeApplicationStartListener($handler);
        $events = new SymfonyEventDispatcher(new RecordingEventDispatcher($listener->process(...)));
        $provider = new ApplicationProvider();
        $provider($events, $exceptionDispatcher, $this->packages());
        $laterHandler = static function (Throwable $throwable): void {
        };
        set_exception_handler($laterHandler);

        try {
            $provider($events, $exceptionDispatcher, $this->packages());

            self::assertSame($laterHandler, self::currentHandler());
        } finally {
            restore_exception_handler();
            restore_exception_handler();
        }
    }

    private static function currentHandler(): ?callable
    {
        $handler = set_exception_handler(static function (Throwable $throwable): void {
        });
        restore_exception_handler();

        return $handler;
    }
    private function packages(): PackageManagerInterface
    {
        $root = $this->createStub(PackageMetadataInterface::class);
        $root->method('version')->willReturn(null);
        $root->method('name')->willReturn('acme/application');
        $packages = $this->createStub(PackageManagerInterface::class);
        $packages->method('root')->willReturn($root);
        return $packages;
    }

}
