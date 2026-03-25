<?php
declare(strict_types=1);

namespace SuperKernel\Framework\Logger;

use Psr\Log\LogLevel;
use Stringable;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\StdoutLoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[
	Provider(StdoutLoggerInterface::class),
]
final class StdoutLogger implements StdoutLoggerInterface
{
	private readonly OutputInterface $output;

	private array $levelMap = [
		LogLevel::EMERGENCY => ['tag' => 'error', 'verbosity' => OutputInterface::VERBOSITY_NORMAL],
		LogLevel::ALERT     => ['tag' => 'error', 'verbosity' => OutputInterface::VERBOSITY_NORMAL],
		LogLevel::CRITICAL  => ['tag' => 'error', 'verbosity' => OutputInterface::VERBOSITY_NORMAL],
		LogLevel::ERROR     => ['tag' => 'error', 'verbosity' => OutputInterface::VERBOSITY_NORMAL],
		LogLevel::WARNING   => ['tag' => 'comment', 'verbosity' => OutputInterface::VERBOSITY_NORMAL],
		LogLevel::NOTICE    => ['tag' => 'info', 'verbosity' => OutputInterface::VERBOSITY_VERBOSE],
		LogLevel::INFO      => ['tag' => 'info', 'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE],
		LogLevel::DEBUG     => ['tag' => 'fg=cyan', 'verbosity' => OutputInterface::VERBOSITY_DEBUG],
	];

	public function __construct(?OutputInterface $output = null)
	{
		$this->output = $output ?? new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
	}

	public function emergency(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug(Stringable|string $message, array $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	public function log($level, Stringable|string $message, array $context = []): void
	{
		if (!isset($this->levelMap[$level])) {
			$level = LogLevel::INFO;
		}

		$config = $this->levelMap[$level];
		if ($this->output->getVerbosity() < $config['verbosity']) {
			return;
		}

		$tag = $config['tag'];
		$this->output->writeln("<$tag>[" . strtoupper($level) . "] $message</$tag>");

		if (!empty($context)) {
			$this->output->writeln(json_encode($context, JSON_PRETTY_PRINT), OutputInterface::VERBOSITY_DEBUG);
		}
	}
}