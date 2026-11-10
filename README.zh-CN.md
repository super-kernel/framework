<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Framework

`super-kernel/framework` 提供 SuperKernel 的应用：一个 Symfony Console 应用，由容器创建，以根包命名，并接入共享的事件分发器和异常分发器。

## 安装

```bash
composer require super-kernel/framework
```

应用由容器创建，并从其他包接收服务：应用还需要提供容器的 `super-kernel/context` 和 `super-kernel/di`、负责事件的 `super-kernel/event-dispatcher`、负责处理失败的 `super-kernel/exception-dispatcher` 和 `super-kernel/logger`，以及构建项目的 SKERNEL。`super-kernel/skeleton` 会安装所有这些包，以及注册命令的 `super-kernel/command`。

## 提供的能力

- 作为 Symfony Console 应用的 `ApplicationInterface`，由容器创建、由入口脚本运行。
- 取自根 `composer.json` 的应用名称和版本。
- 命令的失败交给共享的异常分发器，而无效的控制台输入由 Symfony 自己渲染。
- 一个处理未被捕获的异常的处理器，在应用启动时注册。
- 由应用的 PSR-14 分发器支撑的 Symfony 事件分发器契约，使监听器能够接收 Symfony Console 事件。
- `BeforeApplicationStart`，在应用返回之前发布。

它不会：

- 注册命令，这由 `super-kernel/command` 负责；
- 自己处理异常，也不会在容器就绪之前处理异常；
- 创建服务器、协议回调或日志记录器，也不依赖 Swoole；
- 按 Symfony 事件名称分发；
- 从 Git、已安装的包或 `COMPOSER_ROOT_VERSION` 读取名称或版本。

## 快速开始

入口脚本从容器中解析应用并运行它；`super-kernel/skeleton` 自带的入口脚本是 `bin/bootstrap.php`。将它保存为 `bin/bootstrap.php`：

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use SuperKernel\Context\ApplicationContext;
use SuperKernel\Contract\ApplicationInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

exit(ApplicationContext::getContainer()->get(ApplicationInterface::class)->run());
```

在根 `composer.json` 中写入 `"name": "acme/billing"` 和 `"version": "1.2.3"`，然后构建项目并查询版本：

```bash
skernel build
php bin/bootstrap.php --version
```

它会打印 `acme/billing 1.2.3`。

## 公开接口

| 名称 | 类别 | 用途 |
| --- | --- | --- |
| `SuperKernel\Contract\ApplicationInterface` | 接口 | 从容器解析并运行；`run()` 返回退出状态。 |
| `SuperKernel\Framework\Application` | 类 | `ApplicationInterface` 背后的 Symfony Console 应用。 |
| `name` | 配置键 | 应用名称，取自根 `composer.json`。 |
| `version` | 配置键 | 应用版本，取自根 `composer.json`；可省略。 |
| `SuperKernel\Event\BeforeApplicationStart` | 事件 | 在应用创建之后、返回之前发布一次。 |
| `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` | 接口 | 由容器提供；把每个事件交给 PSR-14 分发器。 |
| `SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler` | 类 | 把未被捕获的异常交给异常分发器；`handle()` 报告一个异常而不结束进程。 |
| `SuperKernel\Framework\Provider\ApplicationProvider` | 类 | 为容器创建应用，或在没有容器的装配边界上创建应用。 |

## 需要了解的行为

- 与 Symfony Console 一样，`run()` 以命令的状态结束进程；在 `Application` 上调用 `setAutoExit(false)` 可以让它改为返回。
- 命令抛出的异常交给 `SuperKernel\Contract\ExceptionDispatcherInterface`，运行以非零状态结束。Symfony Console 异常（例如未知命令、缺少参数或未知选项）由 Symfony 渲染，永远不会到达异常处理器。
- 应用创建之后，未被捕获的异常以同样的方式分发，进程以状态 `1` 退出。容器就绪之前的失败不会被任何处理器处理。
- 容器只创建一次应用，并同步发布 `BeforeApplicationStart`；监听器抛出的异常会向外传播，应用不会被返回。
- 监听器按事件类接收 Symfony Console 事件；Symfony 的字符串事件名称会被忽略。
- 名称和版本从构建元数据中读取：修改它们之后，请重新运行 `skernel build`，或者用会先构建的 `skernel run` 启动。

## 文档

开发者文档介绍了如何运行和嵌入应用、应用的身份、失败处理以及 Symfony Console 事件，并附有完整示例：[SuperKernel Framework](https://github.com/super-kernel/.github/blob/main/docs/components/framework.zh-CN.md)。

## 许可证

基于 MIT 许可证发布，详见 [LICENSE](LICENSE)。
