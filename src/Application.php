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
	public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
	{
		parent::__construct();
	}

	public function run(InputInterface|null $input = null, OutputInterface|null $output = null): int
	{
		$this->eventDispatcher->dispatch(new BootApplication());

		return parent::run($input, $output);
	}
}