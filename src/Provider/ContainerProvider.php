<?php
declare(strict_types=1);

namespace SuperKernel\Framework\Provider;

use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SuperKernel\Annotator\AnnotationCollector;
use SuperKernel\Annotator\AnnotationExtractor;
use SuperKernel\Annotator\Provider\AnnotationCollectorProvider;
use SuperKernel\Attribute\Factory;
use SuperKernel\Attribute\Provider;
use SuperKernel\ComposerResolver\Provider\ComposerJsonReaderProvider;
use SuperKernel\ComposerResolver\Provider\ComposerLockReaderProvider;
use SuperKernel\ComposerResolver\Provider\PackageCollectorProvider;
use SuperKernel\Contract\AnnotationCollectorInterface;
use SuperKernel\Contract\ClassLoaderInterface;
use SuperKernel\Contract\ContainerInterface;
use SuperKernel\Contract\ReflectionCollectorInterface;
use SuperKernel\Di\Container;
use SuperKernel\Di\Contract\DefinitionFactoryInterface;
use SuperKernel\Di\Contract\ResolverFactoryInterface;
use SuperKernel\Di\Definer\ObjectDefiner;
use SuperKernel\Di\Definer\ProviderDefiner;
use SuperKernel\Di\Resolver\FactoryResolver;
use SuperKernel\Di\Resolver\MethodResolver;
use SuperKernel\Di\Resolver\ObjectResolver;
use SuperKernel\PathResolver\PathResolver;
use SuperKernel\PathResolver\Provider\PathResolveAdapterProvider;
use SuperKernel\ProcessHandler\Provider\ProcessHandlerProvider;
use SuperKernel\Reflector\Provider\ReflectionCollectorProvider;

#[
	Provider(ContainerInterface::class),
	Factory,
]
final class ContainerProvider
{
	private static array $attributeClasses = [
		PathResolver::class,
		ObjectDefiner::class,
		MethodResolver::class,
		ObjectResolver::class,
		FactoryResolver::class,
		ProviderDefiner::class,
		ContainerProvider::class,
		ProcessHandlerProvider::class,
		PackageCollectorProvider::class,
		ComposerJsonReaderProvider::class,
		ComposerLockReaderProvider::class,
		PathResolveAdapterProvider::class,
		AnnotationCollectorProvider::class,
		ReflectionCollectorProvider::class,
	];

	/**
	 * @param PsrContainerInterface|null $container
	 *
	 * @return ContainerInterface
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __invoke(?PsrContainerInterface $container = null): ContainerInterface
	{
		if (null === $container) {
			$reflectionCollector = new ReflectionCollectorProvider()();
			$annotationCollector = $this->getTransientAnnotationCollector($reflectionCollector);

			return new Container($annotationCollector, $reflectionCollector)->get(ContainerInterface::class);
		}

		$definitionFactory = $container->get(DefinitionFactoryInterface::class);
		$resolverFactory = $container->get(ResolverFactoryInterface::class);

		$annotationCollectorDefinition = $definitionFactory->getDefinition(AnnotationCollectorInterface::class);
		$annotationCollector = $resolverFactory->getResolver($annotationCollectorDefinition)->resolve($annotationCollectorDefinition);

		$reflectionCollectorDefinition = $definitionFactory->getDefinition(ReflectionCollectorInterface::class);
		$reflectionCollector = $resolverFactory->getResolver($reflectionCollectorDefinition)->resolve($reflectionCollectorDefinition);

		return new Container($annotationCollector, $reflectionCollector);
	}

	private function getTransientAnnotationCollector(ReflectionCollectorInterface $reflectionCollector): AnnotationCollectorInterface
	{
		$composerClassLoader = $this->getComposerClassLoader();

		$annotations = new AnnotationExtractor($this->getTransientClassLoader($composerClassLoader), $reflectionCollector)->getAnnotations();

		return new AnnotationCollector(...$annotations);
	}

	private function getTransientClassLoader(ClassLoader $classLoader): ClassLoaderInterface
	{
		$classMap = [];

		foreach (self::$attributeClasses as $attributeClass) {
			$file = $classLoader->findFile($attributeClass);

			if (false === $file) {
				throw new RuntimeException(sprintf('Class "%s" not found.', $attributeClass));
			}

			$classMap[$attributeClass] = $file;
		}

		return new \SuperKernel\ClassLoader\ClassLoader($classMap);
	}

	private function getComposerClassLoader(): ClassLoader
	{
		/* @var array|false $loaders */
		$loaders = spl_autoload_functions();

		if (false === $loaders) {
			throw new RuntimeException('The autoload queue is not activated');
		}

		foreach ($loaders as $loader) {
			if (is_array($loader) && $loader[0] instanceof ClassLoader) {
				return $loader[0];
			}
		}

		throw new RuntimeException('Composer loader not found.');
	}
}