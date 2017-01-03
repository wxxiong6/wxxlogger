<?php
include __DIR__."/Log.php";


//设置日志目录
Log::getInstance()->setLogPath(__DIR__.'/logs');


//debug
Log::debug('debug');

//info
Log::info('info');

//error
Log::error('error');
