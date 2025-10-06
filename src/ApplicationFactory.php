<?php
declare (strict_types=1);

namespace SuperKernel\Framework;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SuperKernel\Attribute\Contract;
use SuperKernel\Attribute\Factory;
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;

#[
	Contract(ApplicationInterface::class),
	Factory,
]
final readonly class ApplicationFactory
{
	/**
	 * @param ContainerInterface       $container
	 * @param EventDispatcherInterface $eventDispatcher
	 *
	 * @return Application
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __invoke(ContainerInterface $container, EventDispatcherInterface $eventDispatcher): Application
	{
		return ApplicationContext::setContainer($container)->get(Application::class);
	}
}