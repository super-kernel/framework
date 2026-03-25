<?php
declare(strict_types=1);

namespace SuperKernelTest\Framework;

use SuperKernel\Attribute\Autowired;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\StdoutLoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[
	Provider(ApplicationInterface::class, 999),
]
final class Application extends \Symfony\Component\Console\Application implements ApplicationInterface
{
	#[Autowired]
	protected readonly StdoutLoggerInterface $stdoutLogger;

	public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
	{
		$this->stdoutLogger->error('Booting application');
		$this->stdoutLogger->warning('Booting application');
		$this->stdoutLogger->debug('Booting application');
		$this->stdoutLogger->alert('Booting application');
		$this->stdoutLogger->critical('Booting application');
		$this->stdoutLogger->emergency('Booting application');
		$this->stdoutLogger->info('Booting application');
		$this->stdoutLogger->notice('Booting application');

		return Command::SUCCESS;
	}
}