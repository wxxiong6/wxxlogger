[![Build Status](https://travis-ci.org/wxxiong6/wxxlogger.svg?branch=master)](https://travis-ci.org/wxxiong6/wxxlogger)
[![GitHub forks](https://img.shields.io/github/forks/wxxiong6/wxxlogger.svg)](https://github.com/wxxiong6/wxxlogger/network)
[![Packagist](https://img.shields.io/packagist/v/wxxiong6/wxxlogger.svg)](https://packagist.org/packages/wxxiong6/wxxlogger)
![Packagist](https://img.shields.io/packagist/dt/wxxiong6/wxxlogger)
php 日志类
====
默认日志文件名是application.log
支持按天生成日志文件，每个日志文件内容可自定义大小。

### 特点：
轻量、日志分级、高效、日志内容丰富。
日志会缓存在内存中，当日志行数或日志buffer数超过定义的数时写入日志文件。
未超过时，会在程序运行结束后写入日志文件。

```
17-12-14 04:33:19.6806[warn]:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75334][0.0.0.0]  : warn:1513225999
 /Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
17-12-14 04:33:23.6514[warn]:[Wxxiong6\WxxLogger\WxxLoggerTest->testWarn][75358][0.0.0.0]  : warn:1513226003
 /Sites/wxxlogger/WxxLoggerTest.php file:(line 42)
```

### 安装

```
composer require wxxiong6/wxxlogger
```
### 初始化配置
```PHP
    use wxxiong6\wxxLogger\WxxLogger;
    $config = [
      'defaultTemplate' = '%T|%L|%P|%I|%Q|%C',
      'logPath' => __DIR__.'/runtime/logs',
      'maxLogFiles' => 5,
      'traceLevel'  => 0,
      'maxFileSize' => 10240,
      'logFile'     => 'app.log',
      'levels'      => ['error','warning', 'info','debug'],
    ];
    WxxLogger::getInstance()->setConfig($config);

    // 单个属性修改可以如用如下方法
    WxxLogger::getInstance()->setDefaultTemplate('%T|%L|%P|%I|%Q|%C');
    WxxLogger::getInstance()->setXXX($val);

    WxxLogger::error(['mes'=>'error','code'=>100], '123123');
    WxxLogger::debug('debug');
    WxxLogger::info('debug');
```
#### traceLevel
   显示堆栈层数。参数为0时，日志信息少，但日志内容简洁。
#### prefix
   日志回调函数，可通过些函数显示日志自定义标识
#### levels
   定入日志级别，未定义的级别不会写入日志中
 ### 自定义模板参数
 - %L - Level 日志级别。
 - %T - DateTime 如2019-12-17 19:17:02
 - %Q - RequestId 区分单次请求，如没有调用setRequestId($string)方法，则在初始化请求时，采用内置的uniqid()方法生成的惟一值。
 - %H - HostName 主机名。
 - %P - ProcessId 进程ID。
 - %I - Client IP 来源客户端IP; Cli模式下为local。取值优先级为：HTTP_X_REAL_IP > HTTP_X_FORWARDED_FOR > REMOTE_ADDR
 - %C - Class::Action 类名::方法名，如UserService::getUserInfo。不在类中使用时，记录函数名
 - %S - 占位符

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
### changelog：
    v1.0.0 第一版发布
    v2.0.0 增加是否切割日志、是否显示毫秒
           修改日志写入文件方法，合并日志后，调用一次日志
           修改时间函数，默认加关闭毫秒
           修改traceLevel=0时间，category默认时间文件名及行号
    v2.0.1 增加日志模板自定义功能
           增加日志级别
    v3.0.0 重构项目

