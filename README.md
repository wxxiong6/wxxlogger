php 日志类 
====
默认日志文件名是application.log
支持按天生成日志文件，每个日志文件内容可自定义大小。

### 特点：
轻量、分级、高效、定位问题快

```
17-12-14 04:33:19.6806<warn>:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75334][0.0.0.0]  : warn:1513225999  
 /Users/xiong/Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
17-12-14 04:33:23.6514<warn>:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75358][0.0.0.0]  : warn:1513226003  
 /Users/xiong/Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
```

### 安装

```
composer require wxxiong6/wxxlogger
```
### 初始化配置
```PHP
use xwxlogger/WxxLogger as Logger;
 $config = [
                'LogPath' => __DIR__.'/runtime/logs',
                'maxLogFiles' => 5,
                'traceLevel'  => 2,
                'maxFileSize' => 10240,
                'logFile'     => 'app.log',
                'levels'      => ['error','warn'],
                'prefix'      => function () {
                    return "[ip][userID][sessionID]";
                },
            ];
  WxxLogger::getInstance()->setConfig($config);
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
