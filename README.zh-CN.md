<p align="right">
  <a href="README.md">English</a> | <strong>中文文档</strong>
</p>

# SuperKernel Framework

`super-kernel/framework` 是 SuperKernel 的 Symfony Console 宿主和应用身份适配器。它实现共享的 `SuperKernel\Contract\ApplicationInterface`，并把这一公开契约限定为 `run(): int`。具体能力由应用所运行的命令决定；Framework 不负责服务器构造、协议回调、日志器构造或其他组件的生命周期，也不依赖 Swoole。

`SuperKernel\Framework\Application` 继承 Symfony Console 的输入、输出、命令和执行 API。具体类上仍可使用 Symfony 可选的输入和输出参数，而通过共享接口调用的代码只需调用 `run()`：

```php
use SuperKernel\Contract\ApplicationInterface;

$status = $container->get(ApplicationInterface::class)->run();
```

Symfony Console 的自动退出行为保持不变。嵌入式调用方和测试可以通过具体应用上继承来的 `setAutoExit(false)` 方法禁用它。

## 应用装配

容器通过 `SuperKernel\Framework\Provider\ApplicationProvider` 提供 `ApplicationInterface`。每次解析都会创建一个 `Application`，应用根包的名称和声明的版本，安装 Symfony 事件调度器，然后在返回之前用该应用同步分发 `SuperKernel\Event\BeforeApplicationStart`。启动监听器的失败会原样传播，并阻止工厂返回应用；工厂不会调用 `run()`。`super-kernel/command` 借助这个事件安装其命令加载器。

在不由 DI 管理的场景中，装配边界可以直接提供所需的三个服务：

```php
use SuperKernel\Framework\Provider\ApplicationProvider;

$application = new ApplicationProvider()($events, $exceptions, $packageManager);
```

这三个参数依次是 Symfony 的 `EventDispatcherInterface`、共享的 `ExceptionDispatcherInterface` 和 `PackageManagerInterface`。之后的启动监听器可以使用 Symfony 的 setter；替换了 `ApplicationInterface` 的应用需要自行负责其实现的行为。

## 应用身份

根 `composer.json` 是 Console 应用名称和版本的来源：

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

`super-kernel/skeleton` 项目模板没有声明版本，因此其 `--version` 输出为 `super-kernel/skeleton`。名称和版本也会出现在 Console 的 help/list 标题和描述器的身份字段中。扫描诊断可能按应用日志器的配置出现在 Console 输出之前；其默认的 stdout 传输可能影响机器可读的命令输出。

这两个值都在 `BeforeApplicationStart` 之前设置。省略版本时，Symfony 的默认版本保持不变，只显示包名。Framework 不会对这两个值做裁剪、校验、推断、规范化或警告。`COMPOSER_ROOT_VERSION`、VCS 元数据、依赖包版本和 `InstalledVersions` 都不是身份来源。

显示方式由 Symfony 控制：等于 `UNKNOWN` 的版本会被隐藏，空版本保留分隔符，格式标记遵循 Symfony 的输出规则。`getVersion()` 仍返回传入的字符串。请使用 Composer 接受、并且你希望在 Console 中显示的值。

不需要应用载体类、注解、main 方法或显式的入口类设置。启动脚本在取得容器之后解析并运行 `ApplicationInterface`。

PCNTL、Process 和 Exec 使用扫描成功后恢复的包元数据。Phar 使用构建时持久化的根包，因此修改身份或源码声明之后，请重新生成扫描快照和打包产物。运行时的 manifest 不会取代已恢复的快照，缓存缺失或损坏仍然是错误。

## 异常处理

`Application` 把到达 Symfony `renderThrowable()` 边界的每个异常都交给共享的 `ExceptionDispatcherInterface`。Symfony 对 PHP Error 的捕获保持启用，因此命令中的异常和错误进入同一条异常处理链，同时 Symfony 保持其正常的自动退出和非零失败状态。

在 `BeforeApplicationStart` 期间，Framework 以 `PHP_INT_MAX` 优先级（先于普通的启动监听器）把 `SuperKernel\Framework\ExceptionHandler\ProcessExceptionHandler` 注册为进程的未捕获异常处理器。它把其他地方没有处理的异常交给同一个分发器，然后以状态 `1` 结束失败的进程。它不处理警告、不恢复执行，也不定义协议专用的恢复动作。

在 DI 容器和分发器就绪之前没有任何异常处理。在那之前发生的失败说明启动过程存在需要修复的缺陷。

## Symfony 事件调度器适配

容器通过 `SuperKernel\Framework\EventDispatcher\SymfonyEventDispatcher` 提供 Symfony 的事件调度器契约，它把每个事件对象交给 `Psr\EventDispatcher\EventDispatcherInterface`。因此 Symfony Console 的事件会到达应用的 PSR-14 监听器。

Symfony 可选的字符串事件名会被忽略。监听器的选择取决于事件对象的类或接口兼容性；不支持按字符串名称订阅。
