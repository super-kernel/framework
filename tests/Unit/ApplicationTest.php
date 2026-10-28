<?php

declare(strict_types=1);

namespace SuperKernel\Framework\Tests\Unit;

use Error;
use PHPUnit\Framework\TestCase;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ExceptionDispatcherInterface;
use SuperKernel\Framework\Application;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ApplicationTest extends TestCase
{
    /**
     * These assertions protect the public interface contract even when static types already imply it.
     *
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testAdaptsSymfonyConsoleToTheSharedApplicationIdentity(): void
    {
        $application = new Application($this->createStub(ExceptionDispatcherInterface::class));

        $this->assertInstanceOf(SymfonyApplication::class, $application);
        $this->assertInstanceOf(ApplicationInterface::class, $application);
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRunsThroughTheInheritedConsoleLifecycle(): void
    {
        $application = new Application($this->createStub(ExceptionDispatcherInterface::class));
        $output = new BufferedOutput();
        $application->setAutoExit(false);

        $status = $application->run(new ArrayInput([
            'command' => 'list',
        ]), $output);

        $this->assertSame(0, $status);
        $this->assertNotSame('', $output->fetch());
    }

    /** @throws Throwable When fixture setup or the operation under test throws */
    public function testRoutesPhpErrorsThroughTheInjectedExceptionDispatcher(): void
    {
        $throwable = new Error('Application failure.');
        $dispatcher = $this->createMock(ExceptionDispatcherInterface::class);
        $output = new BufferedOutput();
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($throwable));

        $application = new Application($dispatcher);
        $application->setAutoExit(false);
        $application->addCommand(new class($throwable) extends Command {
            public function __construct(private readonly Throwable $throwable)
            {
                parent::__construct('app:fail');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                throw $this->throwable;
            }
        });

        $status = $application->run(new ArrayInput([
            'command' => 'app:fail',
        ]), $output);

        self::assertSame(1, $status);
    }
}
