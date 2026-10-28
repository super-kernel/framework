<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler;
use Throwable;

use function restore_exception_handler;
use function set_exception_handler;

final class ProcessExceptionHandlerTest extends TestCase
{
    public function testRegistersItselfOnceAsTheCurrentProcessHandler(): void
    {
        $originalSentinel = static function (Throwable $throwable): void {
        };
        $probeSentinel = static function (Throwable $throwable): void {
        };
        set_exception_handler($originalSentinel);

        try {
            $handler = new ProcessExceptionHandler(
                $this->createStub(ExceptionDispatcherInterface::class),
            );
            $handler->register();
            $handler->register();

            $installed = set_exception_handler($probeSentinel);

            self::assertSame($handler, $installed);
        } finally {
            restore_exception_handler();
            restore_exception_handler();
            restore_exception_handler();
        }
    }

    public function testDispatchesAnUncaughtProcessThrowable(): void
    {
        $throwable = new RuntimeException('Uncaught process failure.');
        $dispatcher = $this->createMock(ExceptionDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($throwable));

        new ProcessExceptionHandler($dispatcher)->handle($throwable);
    }
}
