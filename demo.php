<?php
include __DIR__."/Logger.php";


//设置日志目录
Logger::getInstance()->setLogPath(__DIR__.'/logs');


//debug
Logger::debug('debug');

//info
Logger::info('info');

//error
Logger::error('error');
