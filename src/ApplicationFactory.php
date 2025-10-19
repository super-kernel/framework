<?php
declare (strict_types=1);

namespace SuperKernel\Framework;

use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\Contract\ApplicationInterface;
#[
    Provider(ApplicationInterface::class),
    Factory,
]
final readonly class ApplicationFactory
{
    public function __invoke(Application $application): ApplicationInterface
    {
        return $application;
    }
}