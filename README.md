# UtilDebug

由程序员主动在业务上调用，触发Error Log，不同于fpm日志，此日志产生单独的文件，便于开发者查看及定位

> 需开启fpm错误日志，且UtilDebug日志将与`error_log`在统一目录下，按日期自动归档日志

#### 配置

```
// php.ini
display_errors = "On"  // 开启fpm错误日志
error_log = /tmp/fpm.error.log  // 路径
```

#### 使用

```php
<?php
  // Notice
  UtilDebug::notice("message");
  UtilDebug::notice("message", new \Exception("Notice"));

  // Warning
  UtilDebug::warning("message");
  UtilDebug::warning("message", new \Exception("Warning"));

  // Error
  UtilDebug::error("message");
  UtilDebug::error("message", new \Exception("Error"));
```



# UtilDebug

由程序员主动在业务上调用，触发Error Log，不同于fpm日志，此日志产生单独的文件，便于开发者查看及定位

> 需开启fpm错误日志，且UtilDebug日志将与`error_log`在统一目录下，按日期自动归档日志

#### 配置

```
// php.ini
display_errors = "On"  // 开启fpm错误日志
error_log = /tmp/fpm.error.log  // 路径
```

#### 使用

```php
<?php
  // Notice
  UtilDebug::notice("message");
  UtilDebug::notice("message", new \Exception("Notice"));

  // Warning
  UtilDebug::warning("message");
  UtilDebug::warning("message", new \Exception("Warning"));

  // Error
  UtilDebug::error("message");
  UtilDebug::error("message", new \Exception("Error"));
```