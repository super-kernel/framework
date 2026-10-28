<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SuperKernel\Attribute\FactoryProvider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\PackageMetadataInterface;
use SuperKernel\Event\BeforeApplicationStart;
use SuperKernel\Framework\Application;
use SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher;
use SuperKernel\Framework\Provider\ApplicationProvider;
use SuperKernel\Framework\Tests\Fixture\RecordingEventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class ApplicationProviderTest extends TestCase
{
    public function testDeclaresTheSharedFactoryProviderContract(): void
    {
        $provider = new ReflectionClass(ApplicationProvider::class);
        $attributes = $provider->getAttributes(FactoryProvider::class);
        $constructor = $provider->getConstructor();
        $invoke = $provider->getMethod('__invoke');

        $this->assertTrue($provider->isFinal());
        $this->assertTrue($provider->isReadOnly());
        $this->assertCount(1, $attributes);
        $this->assertSame(ApplicationInterface::class, $attributes[0]->newInstance()->className);
        $this->assertNull($constructor);
        $this->assertTrue($invoke->isPublic());
        $this->assertFalse($invoke->isStatic());
        $this->assertSame(ApplicationProvider::class, $invoke->getDeclaringClass()->getName());
        $this->assertSame(3, $invoke->getNumberOfRequiredParameters());
        $this->assertCount(3, $invoke->getParameters());
        $this->assertSame(PackageManagerInterface::class, (string) $invoke->getParameters()[2]->getType());
        $this->assertFalse($invoke->getParameters()[2]->isOptional());
        $this->assertSame(
            EventDispatcherInterface::class,
            (string) $invoke->getParameters()[0]->getType(),
        );
        $this->assertSame(
            ExceptionDispatcherInterface::class,
            (string) $invoke->getParameters()[1]->getType(),
        );
        $this->assertSame(ApplicationInterface::class, (string) $invoke->getReturnType());
    }

    /**
     * @throws Throwable if application configuration unexpectedly fails
     */
    public function testPublishesEachConfiguredApplicationBeforeReturningIt(): void
    {
        $recordingDispatcher = new RecordingEventDispatcher(static function (object $event): void {
            self::assertInstanceOf(BeforeApplicationStart::class, $event);
            $application = $event->application();
            self::assertInstanceOf(Application::class, $application);
            self::assertInstanceOf(SymfonyEventDispatcher::class, $application->getDispatcher());
            self::assertSame('acme/application', $application->getName());
            self::assertSame('1.2.3', $application->getVersion());
        });
        $dispatcher = new SymfonyEventDispatcher($recordingDispatcher);
        $exceptionDispatcher = $this->createStub(ExceptionDispatcherInterface::class);
        $provider = new ApplicationProvider();

        $first = $provider($dispatcher, $exceptionDispatcher, $this->packages());
        $second = $provider($dispatcher, $exceptionDispatcher, $this->packages());

        $this->assertInstanceOf(Application::class, $first);
        $this->assertInstanceOf(Application::class, $second);
        $this->assertNotSame($first, $second);
        $this->assertSame($dispatcher, $first->getDispatcher());
        $this->assertSame($dispatcher, $second->getDispatcher());
        $this->assertCount(2, $recordingDispatcher->events);
        $firstEvent = $recordingDispatcher->events[0];
        $secondEvent = $recordingDispatcher->events[1];
        $this->assertInstanceOf(BeforeApplicationStart::class, $firstEvent);
        $this->assertInstanceOf(BeforeApplicationStart::class, $secondEvent);
        $this->assertSame($first, $firstEvent->application());
        $this->assertSame($second, $secondEvent->application());
    }

    public function testPropagatesTheOriginalStartupListenerFailure(): void
    {
        $failure = new LogicException('Startup listener failed.');
        $dispatcher = new SymfonyEventDispatcher(new RecordingEventDispatcher(
            static fn(object $event): never => throw $failure,
        ));
        $exceptionDispatcher = $this->createStub(ExceptionDispatcherInterface::class);

        try {
            new ApplicationProvider()($dispatcher, $exceptionDispatcher, $this->packages());
            self::fail('The application must not be returned after a startup listener fails.');
        } catch (Throwable $caught) {
            self::assertSame($failure, $caught);
        }
    }
    private function packages(): PackageManagerInterface
    {
        $root = $this->createStub(PackageMetadataInterface::class);
        $root->method('version')->willReturn('1.2.3');
        $root->method('name')->willReturn('acme/application');
        $packages = $this->createStub(PackageManagerInterface::class);
        $packages->method('root')->willReturn($root);
        return $packages;
    }

}
