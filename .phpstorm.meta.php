<?php
declare(strict_types=1);

namespace PHPSTORM_META {
	// Reflect
	override(\Psr\Container\ContainerInterface::get(0), map(['' => '@']));
	override(\SuperKernel\Contract\ContainerInterface::get(0), map(['' => '@']));
}
