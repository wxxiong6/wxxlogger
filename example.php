<?php
use wxxiong6\logger\Logger;

include __DIR__.'/vendor/autoload.php';

   $config = [
       'logPath' => __DIR__.'/runtime/logs',
       'maxLogFiles' => 5,
      'traceLevel' => 0,
       'maxFileSize' => 10240,
      'logFile' => 'app.log',
      'levels' => ['error', 'warning', 'info', 'debug'],
    ];
    $i = 1;
Logger::getInstance()->setConfig($config);
$msg = '首先要知道某一部分上锁';
   while (true) {
       Logger::error($msg, $i++);
       Logger::debug($msg, $i++);
       Logger::alert($msg, $i++);
       Logger::info($msg, $i++);
       Logger::debug($msg, $i++);
       // 立刻输出
       //Logger::getInstance()->flush();

       Logger::debug($msg, $i++);
       echo $i, "\n";
   }
