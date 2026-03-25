<?php
declare (strict_types=1);

namespace SuperKernel\Framework\Provider;

use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Framework\Application;

#[
	Provider(ApplicationInterface::class),
	Factory,
]
final readonly class ApplicationProvider
{
	/**
	 * @param Application $application
	 *
	 * @return ApplicationInterface
	 */
	public function __invoke(Application $application): ApplicationInterface
	{
		return $application;
	}
}