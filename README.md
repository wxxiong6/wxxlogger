php 日志类 
====

## 特点：日志分级，批量写入，减少IO,提高性能
   自动定位到写日志的文件及行号方便找到问题

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
