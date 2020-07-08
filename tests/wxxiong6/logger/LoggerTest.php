<?php
/**
 * @author  wxxiong <wxxiong6@gmail.com>
 * @version v2.0.1
 * @see    https://github.com/wxxiong6/wxxlogger/blob/master/README.md
 */

namespace wxxiong6\logger\tests;

use wxxiong6\logger\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public $logger;
    public $logDir;
    public $logFile;
    public $logPath;
    public $logParts;
    public $content;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {

        parent::__construct($name, $data, $dataName);
        $this->logger = Logger::getInstance();
        $this->logDir = dirname(dirname(dirname(__DIR__))) . '/runtime/logs';
        $this->logFile = 'app.log';
        $this->logPath = $this->logDir . '/' . $this->logFile;
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        $config = [
            'logPath' => $this->logDir,
            'maxLogFiles' => 5,
            'traceLevel' => 0,
            'maxFileSize' => 10240,
            'levels'      => ['error','warning', 'alert', 'info', 'debug'],
            'logFile' => 'app.log',
        ];

        $this->logger->setConfig($config);
        Logger::info("info.log");
        Logger::warn("warn.log");
        Logger::debug("debug.log");
        Logger::alert("alert.log");
        Logger::error("error.log");
        $this->logger->flush();
        $this->assertFileExists($this->logPath);
        $this->content = file($this->logPath);
    }

    public function testSetConfig()
    {
        $this->assertObjectHasAttribute('_prefix', $this->logger);
    }

    public function testGetInstance()
    {
        $this->assertObjectHasAttribute('_maxFileSize', $this->logger);
    }

    public function testInfo()
    {
        $name = 'info.log';
        $arr = explode('|', $this->content[0]);
        $this->assertNotEmpty($arr);
        $this->assertEquals('INFO', trim($arr[1]));
        $this->assertEquals($name, trim($arr[6]));
    }

    public function testWarn()
    {
        $name = 'warn.log';
        $arr = explode('|', $this->content[1]);
        $this->assertNotEmpty($arr);
        $this->assertEquals('WARNING', trim($arr[1]));
        $this->assertEquals($name, trim($arr[6]));
    }

    public function testDebug()
    {
        $name = 'debug.log';
        $arr = explode('|', $this->content[2]);
        $this->assertNotEmpty($arr);
        $this->assertEquals('DEBUG', trim($arr[1]));
        $this->assertEquals($name, trim($arr[6]));
    }

    public function testAlert()
    {
        $name = 'alert.log';
        $arr = explode('|', $this->content[3]);
        $this->assertNotEmpty($arr);
        $this->assertEquals('ALERT', trim($arr[1]));
        $this->assertEquals($name, trim($arr[6]));
    }


    public function testError()
    {
        $name = 'error.log';
        $arr = explode('|', $this->content[4]);
        $this->assertNotEmpty($arr);
        $this->assertEquals('ERROR', trim($arr[1]));
        $this->assertEquals($name, trim($arr[6]));
    }

    public function __destruct()
    {
//
    }
}
