<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Contract\PackageManagerInterface;
use SuperKernel\Contract\PackageMetadataInterface;
use SuperKernel\Event\BeforeApplicationStart;
use SuperKernel\Framework\Application;
use SuperKernel\Framework\Provider\ApplicationProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class ApplicationIdentityTest extends TestCase
{
    /** @return iterable<string, array{?string}> */
    public static function versions(): iterable
    {
        yield 'omitted' => [
            null,
        ];
        yield 'ordinary' => [
            '1.0.0-dev',
        ];
        yield 'empty' => [
            '',
        ];
        yield 'whitespace' => [
            ' release ',
        ];
        yield 'control characters' => [
            "build\n\x00\t",
        ];
        yield 'Symfony sentinel' => [
            'UNKNOWN',
        ];
        yield 'prefix and suffix' => [
            'v1.2.3+custom',
        ];
        yield 'arbitrary string' => [
            'not a Composer version',
        ];
    }

    /** @throws Throwable When application assembly fails */
    #[DataProvider('versions')]
    public function testRootIdentityIsAvailableUnchangedToTheFirstStartupListener(?string $version): void
    {
        $root = $this->createMock(PackageMetadataInterface::class);
        $root->expects(self::once())->method('name')->willReturn('acme/application');
        $root->expects(self::once())->method('version')->willReturn($version);
        $packages = $this->createMock(PackageManagerInterface::class);
        $packages->expects(self::once())->method('root')->willReturn($root);
        $packages->expects(self::never())->method('all');
        $packages->expects(self::never())->method('packageForClass');
        $events = $this->createMock(EventDispatcherInterface::class);
        $observed = null;
        $events->expects(self::once())->method('dispatch')->willReturnCallback(
            static function (object $event) use ($version, &$observed): object {
                self::assertInstanceOf(BeforeApplicationStart::class, $event);
                $observed = $event->application();
                self::assertInstanceOf(Application::class, $observed);
                self::assertSame('acme/application', $observed->getName());
                self::assertSame($version ?? 'UNKNOWN', $observed->getVersion());

                return $event;
            },
        );
        $application = new ApplicationProvider()(
            $events,
            $this->createStub(ExceptionDispatcherInterface::class),
            $packages,
        );
        self::assertSame($observed, $application);
    }

    /** @return iterable<string, array{?string, string}> */
    public static function consoleVersions(): iterable
    {
        yield 'omitted' => [
            null,
            "acme/application\n",
        ];
        yield 'ordinary' => [
            '1.0.0-dev',
            "acme/application 1.0.0-dev\n",
        ];
        yield 'empty' => [
            '',
            "acme/application \n",
        ];
        yield 'Symfony sentinel' => [
            'UNKNOWN',
            "acme/application\n",
        ];
        yield 'formatter markup' => [
            '<info>build</info>',
            "acme/application build\n",
        ];
    }

    /** @throws Throwable When application assembly or execution fails */
    #[DataProvider('consoleVersions')]
    public function testPresentationRemainsOwnedBySymfony(?string $version, string $expected): void
    {
        $root = $this->createStub(PackageMetadataInterface::class);
        $root->method('name')->willReturn('acme/application');
        $root->method('version')->willReturn($version);
        $packages = $this->createStub(PackageManagerInterface::class);
        $packages->method('root')->willReturn($root);
        $application = new ApplicationProvider()(
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(ExceptionDispatcherInterface::class),
            $packages,
        );
        self::assertInstanceOf(Application::class, $application);
        $application->setAutoExit(false);
        $output = new BufferedOutput(decorated: false);
        self::assertSame(0, $application->run(new ArrayInput([
            '--version' => true,
        ]), $output));
        self::assertSame($expected, $output->fetch());
        self::assertSame($version ?? 'UNKNOWN', $application->getVersion());
    }
}
