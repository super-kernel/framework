<?php
declare (strict_types=1);

namespace SuperKernel\Framework;

use RuntimeException;
use SuperKernel\Contract\ApplicationInterface;
use SuperKernel\Contract\ComposerInterface;

/**
 * @Application
 * @\SuperKernel\Framework\Application
 */
final readonly class Application implements ApplicationInterface
{

	public function __construct(ComposerInterface $composer)
	{
		if (defined('ROOT_PATH')) {
			throw new RuntimeException('Root path is defined in configuration.');
		}
		define('ROOT_PATH', $composer->getRootPath());
	}

	public function run(): void
	{
		var_dump(545);
	}
}