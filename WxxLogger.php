<?php
namespace Wxxiong6\WxxLogger;
use Exception;
/**
 * 日志类
 * 需要手动创建日志目录,
 * 默认当前目录
 * 默认日志文件名是application.log。
 * @author wxxiong <wxxiong6@gmail.com>
 * @version   v1.0.1
 */
class WxxLogger
{

    /**
     *  代表发生了最严重的错误，会导致整个服务停止（或者需要整个服务停止）。
     *  简单地说就是服务死掉了。
     * @var string
     */
    const LEVEL_FATAL = 'fatal';

    /**
     *  代表发生了必须马上处理的错误。此类错误出现以后可以允许程序继续运行，
     *  但必须马上修正，如果不修正，就会导致不能完成相应的业务。
     * @var string
     */
    const LEVEL_ERROR   = 'error';

    /**
     * 发生这个级别问题时，处理过程可以继续，但必须对这个问题给予额外关注。
     * @var string
     */
    const LEVEL_WARN = 'warn';

    /**
     *  此输出级别常用语业务事件信息。例如某项业务处理完毕，
     *  或者业务处理过程中的一些信息。
     * @var string
     */
    const LEVEL_INFO    = 'info';

    /**
     * 此输出级别用于开发阶段的调试，可以是某几个逻辑关键点的变量值的输出，
     * 或者是函数返回值的验证等等。业务相关的请用info
     * @var string
     */
    const LEVEL_DEBUG   = 'debug';

    /**
     * @var integer 日志内存最大行数
     */
    public $autoFlush = 10000;

    /**
     * @var array 日志信息
     */
    private $_logs = [];

    /**
     * @var integer 数量的日志消息
     */
    private $_logCount = 0;

    /**
     * @var int 限制返回堆栈帧的数量
     */
    private $_traceLevel = 9;
    /**
     * @var integer maximum log file size
     */
    private $_maxFileSize = 1024; // in KB

    /**
     * @var integer 最大日志文件数
     */
    private $_maxLogFiles = 5;

    /**
     * @var string 日志文件目录
     */
    private $_logPath;

    /**
     * @var string 日志文件名称
     */
    private $_logFile = 'application.log';

    /**
     * @var WxxLogger
     */
    private static $_instance;

    private function __construct(){}
    private function __clone(){}

    public function __set($name, $value)
    {
        $property = '_'.$name;
        $this->$property = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }
    /**
     * 获取对象
     * @return WxxLogger
     */
    public static function getInstance(){
        if (!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * 设置配置文件
     * @param array $config
     */
    public function setConfig(array $config){
        foreach ($config as $key => $val){
            $func = 'set'.ucfirst($key);
            if(method_exists(__CLASS__, $func)){
                call_user_func_array([__CLASS__, $func], [$val]);
            }
        }
    }

    /**
     * @return string directory storing log files. Defaults to application runtime path.
     */
    public function getLogPath()
    {
        if($this->_logPath ===null)
            $this->setLogPath(__DIR__);
        return $this->_logPath;
    }

    /**
     *  设置日志目录
     * @param $value directory for storing log files.
     * @throws Exception if the path is invalid
     */
    public function setLogPath($value)
    {
        $this->_logPath = realpath($value);
        if($this->_logPath===false || !is_dir($this->_logPath) || !is_writable($this->_logPath))
            throw new Exception('logPath'."{$value}".' does not point to a valid directory.
			 Make sure the directory exists and is writable by the Web server process.');
    }

    /**
     * @return string 日志文件名称 默认 'application.log'.
     */
    public function getLogFile()
    {
        return $this->_logFile;
    }

    /**
     * @param string $value 日志文件名称
     */
    public function setLogFile($value)
    {
        $this->_logFile=$value;
    }

    /**
     * @return integer maximum 以千字节(KB)的日志文件大小。默认为1024(1 mb)。
     */
    public function getMaxFileSize()
    {
        return $this->_maxFileSize;
    }

    /**
     * @param integer $value maximum log file size in kilo-bytes (KB).
     */
    public function setMaxFileSize($value)
    {
        if(($this->_maxFileSize=(int)$value)<1)
            $this->_maxFileSize=1;
    }

    /**
     * @return integer number 最大文件数
     */
    public function getMaxLogFiles()
    {
        return $this->_maxLogFiles;
    }

    /**
     * @param integer $value 设置最大日志文件数
     */
    public function setMaxLogFiles($value)
    {
        if(($this->_maxLogFiles = (int)$value)  < 1)
            $this->_maxLogFiles = 1;
    }

    /**
     * warn
     * @param string $value
     * @param string $category
     * @return bool
     */
    public static function warn ($value, $category = '')
    {
        return self::write($value, self::LEVEL_WARN, $category);
    }

    /**
     * info
     * @param $value
     * @param string $category
     * @return bool
     */
    public static function info ($value, $category = '')
    {
        return self::write($value, self::LEVEL_INFO,  $category);
    }

    /**
     * error
     * @param $value
     * @param string $category
     * @return bool
     */
    public static function error($value, $category = '')
    {
        return self::write($value, self::LEVEL_ERROR,  $category);
    }

    /**
     *  debug
     * @param $value
     * @param string $category
     * @return bool
     */
    public static function debug($value, $category = '')
    {
        return self::write($value, self::LEVEL_DEBUG,  $category);
    }

    /**
     * 写入日志消息
     * @param $message
     * @param string $level
     * @param $category
     * @return bool
     */
    public static function write($message,  $level = self::LEVEL_INFO, $category)
    {
        $obj = self::getInstance();
        if(is_array($message)){
            call_user_func();
        }
        $obj->_logs[] = $obj->getLogInfo($message,  $level, $category);
        $obj->_logCount++;
        if($obj->autoFlush > 0 && $obj->_logCount >= $obj->autoFlush){  //日志行数
            $obj->flush();
        } elseif(intval(memory_get_usage()/1024) >= $obj->_maxFileSize){  //日志内存数
            $obj->flush();
        }
        return true;
    }

    /**
     * 从内存中删除所有记录的消息。
     */
    public function flush()
    {
        $this->onFlush();
        $this->_logs = [];
        $this->_logCount=0;
    }

    public function onFlush()
    {
        $this->processLogs($this->_logs);
    }

    /**
     * 格式化日志信息
     * @param $message
     * @param $level
     * @param $category
     * @param $time
     * @param $file
     * @param $line
     * @param $traces
     * @return string
     */
    protected function formatLogMessage($message, $level,  $category, $time, $file, $line, $traces)
    {
        //获取IP
        $ipAddress = '0.0.0.0';
        if (isset($_SERVER["SERVER_ADDR"])){
            $ipAddress = $_SERVER["SERVER_ADDR"];
        }
        if(($sessionId = session_id()) === '')
            $sessionId = getmypid();
        return  sprintf("%s<%s>:[%s][%s][%s]  : %s  \n %s file:(line %s)\n", $this->udate('y-m-d H:i:s.u', $time), $level, $category, $sessionId, $ipAddress, $message, $file, $line);
    }

    /**
     * 保存日志信息到文件
     * @param array $logs      日志信息
     * @throws Exception
     */
    protected function processLogs(array $logs)
    {
        $logFile=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
        try {
            if(!is_file($logFile))
            {
                touch($logFile);
            }
            if(filesize($logFile) > $this->getMaxFileSize()*1024)
                $this->rotateFiles();

            $fp = fopen($logFile,'a');
            flock($fp,LOCK_EX);
            foreach($logs as $log)
                fwrite($fp,$this->formatLogMessage($log[0], $log[1], $log[2], $log[3], $log[4], $log[5], $log[6]));

            flock($fp,LOCK_UN);
            fclose($fp);
        } catch (Exception $e) {
            throw new Exception('log error:'.$e->getMessage());
        }
    }

    /**
     * Rotates log files.
     */
    protected function rotateFiles()
    {
        $file=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
        $max=$this->getMaxLogFiles();
        for($i=$max;$i>0;--$i)
        {
            $rotateFile=$file.'.'.$i;
            if(is_file($rotateFile))
            {
                if($i===$max)
                    unlink($rotateFile);
                else
                    rename($rotateFile,$file.'.'.($i+1));
            }
        }
        if(is_file($file))
            rename($file,$file.'.1');
    }

    /**
     * 生成文件名、行号和函数名
     * @param $message
     * @param string $level
     * @param string $category
     * @return array
     */
    protected  function getLogInfo ($message,  $level = 'info', $category = '')
    {
        $time = microtime(true);
        $traces = [];
        if ($this->_traceLevel > 0) {
            $count = 0;
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts);
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) &&  strpos($trace['file'], __FILE__) !== 0) {
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->_traceLevel) {
                        break;
                    }
                } else if(!isset($trace['file'], $trace['line'])){
                    $traces[] = $trace;
                    if (++$count >= $this->_traceLevel) {
                        break;
                    }
                }
            }
        }

        if(!empty($category)){
            $category = $category;
        }else if(!empty($traces[1])){
            $category = '';
            if(isset($traces[1]['class']))
                $category .= $traces[1]['class'];
            if(isset($traces[1]['type']))
                $category .= $traces[1]['type'];
            if(isset($traces[1]['function']))
                $category .= $traces[1]['function'];
        } else {
            $category = '-';
        }
        $line = isset($traces[0]['line']) ? $traces[0]['line'] : 0;
        $file = isset($traces[0]['file']) ? $traces[0]['file'] : '';
        return  [$message, $level, $category, $time, $file, $line, $traces];
    }

    /**
     * 毫秒
     * @param string $strFormat
     * @param null $uTimeStamp
     * @return false|string
     */
    private function udate($strFormat = 'u', $uTimeStamp = null)
    {
        if (is_null($uTimeStamp))
        {
            $uTimeStamp = microtime(true);
        }
        $arrTimeStamp = explode('.',$uTimeStamp,2);
        $intMilliseconds = array_pop($arrTimeStamp);

        $strMilliseconds = str_pad($intMilliseconds, 4, '0', STR_PAD_LEFT);
        return date(preg_replace('`(?<!\\\\)u`', $strMilliseconds, $strFormat), $arrTimeStamp[0]);
    }

    public function __destruct(){
        if($this->_logCount > 0)
            $this->flush();
    }
}
