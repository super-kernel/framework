<?php
declare(strict_types=1);

namespace SuperKernel\Framework;

use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Event\BootApplication;

#[
	Provider(ApplicationInterface::class),
	Factory,
]
final class Application extends \Symfony\Component\Console\Application implements ApplicationInterface
{
	public function __invoke(EventDispatcherInterface $eventDispatcher): ApplicationInterface
	{
		$eventDispatcher->dispatch(new BootApplication());

		return $this;
	}
}