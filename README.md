php 日志类 
====
需要手动创建日志目录,默认日志文件名是application.log

### 特点：
轻量级、日志分级、批量写入减少IO提高写入性能。自动定位到写日志的文件及行号,方便找到问题

```
17-11-09 08:52:43.4464<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
17-11-09 08:52:50.0005<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
17-11-09 08:52:52.5704<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
```

### 安装

```
composer require wxxiong6/wxxlogger
```
### 初始化配置
```PHP
use xwxlogger/Logger;
$config = [ 'LogPath' => 'D:\\web\\kyYaf/runtime/logs', 'maxLogFiles' => '5', 'maxFileSize' => '10240', 'logFile' => 'app.log',];
Logger::getInstance()->setConfig($logConfig);
```

### 常用方法

####  debug
```PHP
Logger::debug('debug');
```
####  info
```PHP
Logger::info('info');
```
####  error
```PHP
Logger::error('error');
```
