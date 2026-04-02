<?php
declare(strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Contract\ContainerInterface;

final class ApplicationContext
{
	private static ContainerInterface $container;

	public static function getContainer(): ContainerInterface
	{
		return self::$container;
	}

	public static function setContainer(ContainerInterface $container): ContainerInterface
	{
		self::$container = $container;
		return $container;
	}
}