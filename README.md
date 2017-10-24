# UtilDebug

由程序员主动在业务上调用，触发Error Log，不同于fpm日志，此日志产生单独的文件，便于开发者查看及定位

> 需开启fpm错误日志，且UtilDebug日志将与`error_log`在同一目录下，按日期自动归档日志

#### 配置

```
// php.ini
display_errors = "On"  // 开启fpm错误日志
error_log = /tmp/fpm.error.log  // 路径
```

#### 使用

```php
<?php
  // 只适用于DAEMON进程,或者未重置请求上下文时有效
  \UtilDebug\UtilDebug::getLastError();
  
  // Config file name
  \UtilDebug\UtilDebug::config('app.log');

  // Notice
  \UtilDebug\UtilDebug::notice("message");
  \UtilDebug\UtilDebug::notice("message", new \Exception("Notice"));

  // Warning
  \UtilDebug\UtilDebug::warning("message");
  \UtilDebug\UtilDebug::warning("message", new \Exception("Warning"));

  // Error
  \UtilDebug\UtilDebug::error("message");
  \UtilDebug\UtilDebug::error("message", new \Exception("Error"));
```


