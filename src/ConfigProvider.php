<?php
declare(strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Config\ComposerFactory;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ComposerInterface;

/**
 * @ConfigProvider
 * @\SuperKernel\Framework\ConfigProvider
 */
final class ConfigProvider
{
	public function __invoke(): array
	{
		return [
			'paths'        => [
				__DIR__,
			],
			'dependencies' => [
				ComposerInterface::class    => ComposerFactory::class,
				ApplicationInterface::class => ApplicationFactory::class,
			],
		];
	}
}