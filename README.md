php 日志类 
====

## 特点：日志分级，批量写入，减少IO,提高性能

需要手动创建日志目录,默认日志文件名是application.log。
```PHP
include __DIR__."/Log.php";
```
## 设置日志目录
```PHP
Log::getInstance()->setLogPath(__DIR__.'/logs');
```
## debug
```PHP
Log::debug('debug');
```
## info
```PHP
Log::info('info');
```
## error
Log::error('error');
```
