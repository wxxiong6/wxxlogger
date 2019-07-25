php 日志类 
====
默认日志文件名是application.log
支持按天生成日志文件，每个日志文件内容可自定义大小。

### 特点：
轻量、日志分级、高效、日志内容丰富。
缓存日志在内存中，当日志行数、日志buffer数超过定义的数据写入日志文件。
当行数、buffer没有到定义数时，后在程序结束后写入日志文件

```
17-12-14 04:33:19.6806[warn]:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75334][0.0.0.0]  : warn:1513225999  
 /Users/xiong/Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
17-12-14 04:33:23.6514[warn]:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75358][0.0.0.0]  : warn:1513226003  
 /Users/xiong/Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
```

### 安装

```
composer require wxxiong6/wxxlogger
```
### 初始化配置
```PHP
    use wxxiong6\wxxLogger\WxxLogger;
    $config = [
      'LogPath' => __DIR__.'/runtime/logs',
      'maxLogFiles' => 5,
      'traceLevel'  => 0,
      'maxFileSize' => 10240,
      'logFile'     => 'app.log',
      'levels'      => ['error','warn','debug'],
      'prefix'      => function () {
              return "[ip][userID][sessionID]";
        },
    ];
    WxxLogger::getInstance()->setConfig($config);
    WxxLogger::error(['mes'=>'error','code'=>100], '123123');
    WxxLogger::debug('debug');
```
#### traceLevel
   显示堆栈层数。参数为0时，日志信息少，但日志内容简洁。
#### prefix 
   日志回调函数，可通过些函数显示日志自定义标识
#### levels
   定入日志级别，未定义的级别不会写入日志中


### 常用方法：

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
### changlog：
    v1.0.0 第一版发布
    v2.0.0 增加是否切割日志、是否显示毫秒
           修改多次调用fwrite，合并日志后，调用一次日志
           修改时间时间函数，默认加关闭毫秒
           修改traceLevel=0时间，category默认时间文件名及行号
