<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Framework

`super-kernel/framework` is the Symfony Console host and application identity adapter for SuperKernel. It implements the shared `SuperKernel\Contract\ApplicationInterface` and limits that public contract to `run(): int`. Concrete capabilities are selected by the command that the application runs; Framework does not own server construction, protocol callbacks, logger construction, or another component's lifecycle, and it does not depend on Swoole.

`SuperKernel\Framework\Application` inherits Symfony Console's input, output, command, and execution APIs. Symfony's optional input and output parameters remain available on the concrete class while callers using the shared interface need only invoke `run()`:

```php
use SuperKernel\Contract\ApplicationInterface;

$status = $container->get(ApplicationInterface::class)->run();
```

Symfony Console auto-exit behavior is preserved. Embedded callers and tests may disable it through the inherited `setAutoExit(false)` method on the concrete application.

## Application assembly

The container supplies `ApplicationInterface` through `SuperKernel\Framework\Provider\ApplicationProvider`. Each resolution creates one `Application`, applies the root package's name and declared version, installs the Symfony event dispatcher, and then synchronously dispatches `SuperKernel\Event\BeforeApplicationStart` with that application before returning it. A startup listener's failure propagates unchanged and prevents the factory from returning the application; the factory does not call `run()`. `super-kernel/command` uses this event to install its command loader.

Outside a DI-managed consumer, an assembly boundary can supply the three required services directly:

```php
use SuperKernel\Framework\Provider\ApplicationProvider;

$application = new ApplicationProvider()($events, $exceptions, $packageManager);
```

The arguments are Symfony's `EventDispatcherInterface`, the shared `ExceptionDispatcherInterface`, and `PackageManagerInterface`. Later startup listeners may use Symfony setters, and an application replacing `ApplicationInterface` remains responsible for its own implementation's behavior.

## Application identity

The root `composer.json` is the source of the Console application name and version:

```json
{
    "name": "acme/application",
    "version": "1.2.3"
}
```

```console
$ php bin/bootstrap.php --version
acme/application 1.2.3
```

The `super-kernel/skeleton` project template declares no version, so its `--version` output is `super-kernel/skeleton`. Name and version also appear in Console help/list headers and descriptor identity fields. Scan diagnostics may precede Console output according to the application's logger; its default stdout transport can affect machine-readable command output.

Both values are set before `BeforeApplicationStart`. An omitted version leaves Symfony's default version unchanged and displays only the package name. Framework does not trim, validate, infer, normalize, or warn about either value. `COMPOSER_ROOT_VERSION`, VCS metadata, dependency versions, and `InstalledVersions` are not identity sources.

Symfony controls presentation: a version equal to `UNKNOWN` is hidden, an empty version keeps the separator, and formatter markup follows Symfony's output rules. `getVersion()` still returns the supplied string. Use values that Composer accepts and that you intend to show in your Console.

No application carrier class, Attribute, main method, or explicit entry class setting is required. The bootstrap resolves and runs `ApplicationInterface` after obtaining the container.

PCNTL, Process, and Exec use the package metadata restored after successful scanning. Phar uses the build's persisted root package, so rebuild the scan snapshots and packaged artifact after changing identity or source declarations. The runtime manifest does not replace a restored snapshot, and missing or corrupt caches remain errors.

## Exception handling

`Application` delegates every throwable that reaches Symfony's `renderThrowable()` boundary to the shared `ExceptionDispatcherInterface`. Symfony PHP Error catching remains enabled, so command exceptions and errors enter the same exception chain while Symfony keeps its normal auto-exit and non-zero failure status.

During `BeforeApplicationStart`, at `PHP_INT_MAX` priority before ordinary startup listeners, Framework registers `SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler` as the process's uncaught-throwable handler. It forwards otherwise-unhandled throwables to the same dispatcher and then terminates the failed process with status `1`. It does not handle warnings, recover execution, or define protocol-specific recovery actions.

There is no exception handling before the DI container and dispatcher are ready. A failure at that point indicates a startup bug to repair.

## Symfony event-dispatcher adaptation

The container supplies Symfony's event dispatcher contract through `SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher`, which delegates every event object to `Psr\EventDispatcher\EventDispatcherInterface`. Symfony Console events therefore reach the application's PSR-14 listeners.

Symfony's optional string event name is ignored. Listener selection follows event object class or interface compatibility; string-name subscriptions are not supported.
