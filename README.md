php 日志类 
====

### 特点：日志分级、批量写入减少IO提高写入性能、自动定位到写日志的文件及行号方便找到问题

 
```PHP
17-11-09 08:52:43.4464<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
17-11-09 08:52:50.0005<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
17-11-09 08:52:52.5704<debug>:[TestController->redisAction][13044][127.0.0.1] D:\web\log\application\controllers\Test.php(line 33 )   ddddd
```PHP

需要手动创建日志目录,默认日志文件名是application.log。
```PHP
include __DIR__."/Logger.php";
```
## 设置日志目录
```PHP
Logger::getInstance()->setLogPath(__DIR__.'/logs');
```
## debug
```PHP
Logger::debug('debug');
```
## info
```PHP
Logger::info('info');
```
## error
```PHP
Logger::error('error');
```
