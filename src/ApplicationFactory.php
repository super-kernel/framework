<?php
declare(strict_types=1);

namespace SuperKernel\Framework;

use Psr\Container\ContainerInterface;
use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ComposerInterface;
use SuperKernel\Di\Abstract\AbstractContainerFactory;
use SuperKernel\Di\Container;

/**
 * @ApplicationFactory
 * @\SuperKernel\Framework\ApplicationFactory
 */
final class ApplicationFactory
{
	public function __invoke(): ApplicationInterface
	{
		if (!ApplicationContext::hasContainer()) {
			ApplicationContext::setContainer(new readonly class extends AbstractContainerFactory {
				protected function getDependencies(): array
				{
					return $this->composer?->getDependencies() ?? new ConfigProvider()()['dependencies'];
				}

				public function __invoke(): ContainerInterface
				{
					$container = new Container($this);

					return null === $this->composer
						? new self($container->get(ComposerInterface::class))()
						: $container;
				}
			}());
		}

		return ApplicationContext::getContainer()->get(Application::class);
	}
}