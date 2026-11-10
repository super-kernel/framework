<p align="right">
  <strong>English</strong> | <a href="README.zh-CN.md">中文文档</a>
</p>

# SuperKernel Framework

`super-kernel/framework` provides the application of SuperKernel: a Symfony Console application that the container creates, names after the root package, and connects to the shared event and exception dispatchers.

## Installation

```bash
composer require super-kernel/framework
```

The application is created by the container and receives services from other packages: an application also needs `super-kernel/context` and `super-kernel/di` for the container, `super-kernel/event-dispatcher` for events, and `super-kernel/exception-dispatcher` with `super-kernel/logger` for failures, and SKERNEL to build the project. `super-kernel/skeleton` installs all of them, together with `super-kernel/command`, which registers commands.

## What it provides

- `ApplicationInterface` as a Symfony Console application, which the container creates and an entry script runs.
- The application's name and version, taken from the root `composer.json`.
- Command failures sent to the shared exception dispatcher, while Symfony renders invalid console input itself.
- A handler for throwables that nothing catches, registered when the application starts.
- Symfony's event dispatcher contract backed by the application's PSR-14 dispatcher, so listeners receive Symfony Console events.
- `BeforeApplicationStart`, published before the application is returned.

It does not:

- register commands; `super-kernel/command` does;
- handle exceptions itself, or before the container is ready;
- create servers, protocol callbacks, or loggers, or depend on Swoole;
- dispatch by Symfony event name;
- read the name or version from Git, installed packages, or `COMPOSER_ROOT_VERSION`.

## Quick start

An entry script resolves the application from the container and runs it; `super-kernel/skeleton` ships one as `bin/bootstrap.php`. Save it as `bin/bootstrap.php`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(ApplicationContext::getContainer()->get(ApplicationInterface::class)->run());
```

With `"name": "acme/billing"` and `"version": "1.2.3"` in the root `composer.json`, build the project and ask for the version:

```bash
skernel build
php bin/bootstrap.php --version
```

It prints `acme/billing 1.2.3`.

## Public surface

| Name | Kind | Purpose |
| --- | --- | --- |
| `SuperKernel\Contract\ApplicationInterface` | Interface | Resolved from the container and run; `run()` returns the exit status. |
| `SuperKernel\Framework\Application` | Class | The Symfony Console application behind `ApplicationInterface`. |
| `name` | Configuration key | The application name, from the root `composer.json`. |
| `version` | Configuration key | The application version, from the root `composer.json`; optional. |
| `SuperKernel\Event\BeforeApplicationStart` | Event | Published once the application is created, before it is returned. |
| `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` | Interface | Provided by the container; passes every event to the PSR-14 dispatcher. |
| `SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler` | Class | Sends throwables that nothing catches to the exception dispatcher; `handle()` reports one without ending the process. |
| `SuperKernel\Framework\Provider\ApplicationProvider` | Class | Creates the application for the container, or at an assembly boundary without one. |

## Behavior to know

- `run()` ends the process with the command's status, as Symfony Console does; call `setAutoExit(false)` on `Application` to have it return instead.
- A throwable from a command goes to `SuperKernel\Contract\ExceptionDispatcherInterface`, and the run ends with a non-zero status. Symfony Console exceptions, such as an unknown command, a missing argument, or an unknown option, are rendered by Symfony and never reach exception handlers.
- Once the application is created, a throwable that nothing catches is dispatched the same way and the process exits with status `1`. Nothing handles a failure before the container is ready.
- The container creates the application once and publishes `BeforeApplicationStart` synchronously; an exception from a listener propagates, and the application is not returned.
- Listeners receive Symfony Console events by event class; Symfony's string event name is ignored.
- The name and version are read from the build metadata: run `skernel build` again after changing them, or start with `skernel run`, which builds first.

## Documentation

The developer documentation describes running and embedding the application, its identity, failure handling, and Symfony Console events, with complete examples: [SuperKernel Framework](https://github.com/super-kernel/.github/blob/main/docs/components/framework.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
