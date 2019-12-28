<?php
include __DIR__ . '/WxxLogger.php';
use wxxiong6\wxxLogger\WxxLogger;
   $config = [
       'logPath' => __DIR__.'/runtime/logs',
       'maxLogFiles' => 5,
      'traceLevel'  => 0,
       'maxFileSize' => 10240,
      'logFile'     => 'app.log',
      'levels'      => ['error','warning', 'info','debug'],
    ];
    $i = 1;
WxxLogger::getInstance()->setConfig($config);
$msg = '首先要知道某一部分上锁';
   while (true) {
    WxxLogger::error($msg, $i++);
    WxxLogger::debug($msg, $i++);
    WxxLogger::alert($msg, $i++);
    WxxLogger::info($msg, $i++);
WxxLogger::debug($msg, $i++);
// 立刻输出
//WxxLogger::getInstance()->flush();

WxxLogger::debug($msg, $i++);
echo $i, "\n";
   }