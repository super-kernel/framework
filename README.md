# SuperKernel Framework

`super-kernel/framework` is the Symfony Console host and application identity adapter for SuperKernel. It implements the shared
`SuperKernel\Contract\ApplicationInterface` and limits that public contract to `run(): int`. Concrete capabilities are
selected by the command that the application runs; Framework does not own server construction, protocol callbacks,
logger construction, or another component's lifecycle.

`SuperKernel\Framework\Application` inherits Symfony Console's input, output, command, and execution APIs. Symfony's
optional input and output parameters remain available on the concrete class while callers using the shared interface
need only invoke `run()`:

```php
use SuperKernel\Contract\ApplicationInterface;

$status = $container->get(ApplicationInterface::class)->run();
```

Symfony Console auto-exit behavior is preserved. Embedded callers and tests may disable it through the inherited
`setAutoExit(false)` method on the concrete application.

## Source organization

Paths below are relative to `src/` and the package namespace.

| Directory          | Responsibility                                                                                       |
|--------------------|------------------------------------------------------------------------------------------------------|
| `ExceptionHandler` | `ProcessExceptionHandler`: current-process uncaught-throwable registration and termination.          |
| `Listener`         | `BeforeApplicationStartListener`: registers the injected process handler during application startup. |
| `EventDispatcher`  | Symfony-to-PSR event dispatcher adaptation.                                                          |
| `Provider`         | Application construction and startup-event publication.                                              |

### Public namespaces

Use the following component declarations:

- `ExceptionHandler\ProcessExceptionHandler`

## Application assembly

`SuperKernel\Framework\Provider\ApplicationProvider` supplies `ApplicationInterface`. Its invocation
receives Symfony's `EventDispatcherInterface`, the shared `ExceptionDispatcherInterface`, and the required Contract
`PackageManagerInterface`. It creates one `Application`, applies the root package name and declared version,
installs the Symfony dispatcher,
then synchronously dispatches `SuperKernel\Event\BeforeApplicationStart`
with that application before returning it. Each invocation creates and publishes a fresh instance. A startup listener's
failure propagates unchanged and prevents the factory from returning the application; the factory does not call `run()`.

`super-kernel/command` listens for this event and installs its command loader when the payload is a Symfony Console
application. Framework performs no command-loader installation and does not depend on the Command component.

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

The `super-kernel/skeleton` project template declares no version, so its `--version` output is `super-kernel/skeleton`.
Name and version also appear in Console help/list headers and descriptor identity fields. Scan diagnostics may precede
Console output according to the application's logger; its default stdout transport can affect machine-readable command
output.

`ApplicationProvider` reads `PackageManagerInterface::root()`, calls `setName($root->name())`, and calls `setVersion()`
only when `version()` is not null. Both values are in place before `BeforeApplicationStart`. An omitted version leaves
Symfony's default version unchanged and displays only the package name. The factory does not trim, validate, infer,
normalize, or warn about string contents. File structure and string types are checked by the framework's Composer
reader; version-format validation belongs to the Composer tool. `COMPOSER_ROOT_VERSION`, VCS metadata, dependency
versions, and `InstalledVersions` are not identity sources.

Symfony controls presentation: a version equal to `UNKNOWN` is hidden, an empty version keeps the separator, and
formatter markup follows Symfony's output rules. `getVersion()` still returns the supplied string. Use values that
Composer accepts and that you intend to show in your Console.

No application carrier class, Attribute, main method, or explicit entry class setting is required. The factory does
not query annotation records or load an application class for identity. The bootstrap simply resolves and runs
`ApplicationInterface` after obtaining the container.

PCNTL, Process, and Exec use the package metadata restored after successful scanning. Phar and future restore-only
binary hosts use the build's persisted root package, so rebuild the scan snapshots and packaged artifact after changing
identity or source declarations. The runtime manifest does not replace a restored snapshot. Missing or corrupt caches
remain errors; no cache schema changes are required. This metadata contract does not implement a binary launcher:
current Process/Exec reentry uses a PHP script and `PHP_BINARY`.

### Direct factory calls

Outside a DI-managed consumer, an assembly boundary can supply the three required services:

```php
use SuperKernel\Framework\Provider\ApplicationProvider;

$application = new ApplicationProvider()($events, $exceptions, $packageManager);
```

DI-managed consumers inject `ApplicationInterface`. Framework consumes only Contract metadata ports and does not
depend on the Composer implementation. Later startup listeners may use Symfony setters, and an application replacing
`ApplicationInterface` remains responsible for its own implementation's behavior.

## Exception handling

Framework does not own an exception-handler registry or formatter. `Application` injects the shared
`ExceptionDispatcherInterface` and delegates the throwable from Symfony's `renderThrowable()` boundary. Symfony PHP
Error catching remains enabled, so DI-ready command exceptions and errors enter the same exception chain while Symfony
retains normal auto-exit and non-zero failure-status behavior.

The root bootstrap deliberately installs no pre-DI `try/catch` or bootstrap exception handler. Failures before the DI
container and dispatcher are ready are outside the framework's exception-handling guarantee and indicate a startup
bug to repair. Framework's `Listener\BeforeApplicationStartListener` registers its injected
`ExceptionHandler\ProcessExceptionHandler` during `BeforeApplicationStart`, at `PHP_INT_MAX` priority before ordinary
startup listeners. Listener construction is passive, and repeated events reuse the handler's idempotent registration.
The composition root only obtains the container and resolves and runs `ApplicationInterface`; it does not register the
process handler. The handler forwards otherwise-unhandled
DI-ready throwables directly to the same dispatcher; it does not handle warnings, recover execution, or define
protocol-specific recovery actions, and terminates the failed process with status `1` after dispatch. The composition
root also supplies the dispatcher implementation, its terminal logger, and its concise-by-default configuration.

## Symfony event-dispatcher adaptation

`SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher` is the DI provider for Symfony's dispatcher contract.
It receives `Psr\EventDispatcher\EventDispatcherInterface` and delegates every event object to that PSR-14 dispatcher.
This keeps `super-kernel/event-dispatcher` pure PSR-14 while allowing Symfony Console events to use the application's
listener graph.

Symfony's optional string event name is deliberately ignored. Listener selection therefore follows event object class
or interface compatibility; string-name subscriptions are not part of this adapter's contract.

## Dependency boundary

Framework directly depends on the SuperKernel Contract, Attribute, and Event packages, PSR Event Dispatcher, Symfony
Console, and Symfony Event Dispatcher Contracts. It has no production dependency on Swoole or the SuperKernel Command,
Logger, Server, HTTP Server, or Event Dispatcher implementation packages.

## Development

Use a PHP 8.4 Composer in this package's checkout:

```bash
composer install
composer validate --strict
composer test
composer format:check
composer format
```
