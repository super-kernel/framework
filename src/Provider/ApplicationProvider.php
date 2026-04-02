<?php
declare (strict_types=1);

namespace SuperKernel\Framework\Provider;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Framework\Event\BootApplication;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[
	Provider(ApplicationInterface::class),
	Factory,
]
final readonly class ApplicationProvider
{
	/**
	 * @param ContainerInterface $container
	 *
	 * @return ApplicationInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container): ApplicationInterface
	{
		$eventDispatcher = $container->get(EventDispatcherInterface::class);

		return new class($eventDispatcher) extends Application implements ApplicationInterface {

			public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
			{
				parent::__construct();
			}

			public function run(InputInterface|null $input = null, OutputInterface|null $output = null): int
			{
				$this->eventDispatcher->dispatch(new BootApplication());

				return parent::run($input, $output);
			}
		};
	}
}