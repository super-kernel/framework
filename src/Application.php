<?php
declare (strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Contract\ApplicationInterface;

/**
 * @Application
 * @\SuperKernel\Framework\Application
 */
final readonly class Application implements ApplicationInterface
{
	public function run(): void
	{
		var_dump(545);
	}
}