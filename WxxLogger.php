<?php
/**
 * @author  wxxiong <wxxiong6@gmail.com>
 * @version v2.0.1
 * @link    https://github.com/wxxiong6/wxxlogger/blob/master/README.md
 */
namespace wxxiong6\wxxLogger;
use Exception;
/**
 * Class WxxLogger
 * @package Wxxiong6\WxxLogger
 * 日志类
 * 需要手动创建日志目录,
 * 默认当前目录
 * 默认日志文件名是application.log。
 * * @example
 * <pre>
 *   $config = [
 *      'logPath' => __DIR__.'/runtime/logs',
 *      'maxLogFiles' => 5,
 *      'traceLevel'  => 0,
 *      'maxFileSize' => 10240,
 *      'logFile'     => 'app.log',
 *      'levels'      => ['error','warn','debug'],
 *    ];
 *    WxxLogger::getInstance()->setConfig($config);
 *    WxxLogger::error(['mes'=>'error','code'=>100], '123123');
 *    WxxLogger::debug('debug');
 *      ...
 * </pre>
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
     * 日志前缀
     * @var string
     */
    private $_prefix;
    /**
     * @var integer 日志内存最大行数
     */
    public $autoFlush = 10000;
    /**
     * 记录日志级别
     * @var array
     */
    private $_levels = [
        self::LEVEL_DEBUG,
        self::LEVEL_INFO,
        self::LEVEL_ERROR,
        self::LEVEL_FATAL,
        self::LEVEL_WARN
    ];
    /**
     * @var array 日志信息
     */
    private $_logs = [];
    /**
     * @var integer 数量的日志消息
     */
    private $_logCount = 0;
    /**
     * 建议设置大于2，否则category无法自动显示
     * @var int 限制返回堆栈帧的数量
     */
    private $_traceLevel = 2;
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
    private $_logPath = __DIR__;
    /**
     * @var string 日志文件名称
     */
    private $_logFile = 'application.log';
    /**
     * @var bool  是否切割日志文件
     * @since 2.0.0
     */
    private $_enableRotation = true;
    /**
     * @var bool 开启毫秒.
     * Defaults to false.
     * @since 2.0.0
     */
    private $_microtime = true;

    /**
     * @var string
     * %L - Level 日志级别。
     * %T - DateTime 如2019-12-17 19:17:02
     * %Q - RequestId 区分单次请求，如没有调用setRequestId($string)方法，则在初始化请求时，采用内置的uniqid()方法生成的惟一值。
     * %H - HostName 主机名。
     * %P - ProcessId 进程ID。
     * %I - Client IP 来源客户端IP; Cli模式下为local。取值优先级为：HTTP_X_REAL_IP > HTTP_X_FORWARDED_FOR > REMOTE_ADDR
     * %C - Class::Action 类名::方法名，如UserService::getUserInfo。不在类中使用时，记录函数名
     * %S - 占位符，什么都不做
     */
    private $_defaultTemplate = "%T|%L|%P|%I|%Q|%C|%M";
    /**
     * @var string 分隔符，必须与$defaultTemplate 一致
     */
    private $_separator = '|';
    /**
     * @var WxxLogger
     */
    private static $_instance;

    private function __construct()
    {
    }
    private function __clone()
    {
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'set') === 0) {
            $property = str_replace('set', '', $name);
            $property = '_'.lcfirst($property);
            if (isset($arguments[0])) {
                $this->$property = $arguments[0];
            }
        } else if (strpos($name, 'get') === 0) {
            $property = str_replace('get', '', $name);
            $property = '_'.lcfirst($property);
            return $this->$property;
        }
    }

    /**
     * 设置配置文件
     * @param array $config
     */
    public function setConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $func = 'set'.ucfirst($key);
            call_user_func_array([__CLASS__, $func], [$val]);
        }
    }

    /**
     * 获取对象
     * @propery $
     * @return WxxLogger
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
            register_shutdown_function(function () {
                self::$_instance->flush();
                register_shutdown_function([self::$_instance, 'flush'], true);
            });
        }
        return self::$_instance;
    }

    /**
     *  设置日志目录
     * @param string $logPath 日志目录
     * @throws Exception if the path is invalid
     */
    public function setLogPath($logPath)
    {
        if (!is_dir($logPath)) {
            mkdir($logPath, 0775, true);
        }
        $this->_logPath = $logPath;
    }

    /**
     * @return string 日志文件名称 默认 'application.log'.
     */
    public function getLogFile()
    {
        return $this->_logPath . DIRECTORY_SEPARATOR . $this->_logFile;
    }


    /**
     * @param integer $value maximum log file size in kilo-bytes (KB).
     */
    public function setMaxFileSize($value)
    {
        if (($this->_maxFileSize = (int)$value)  < 1) {
            $this->_maxFileSize=1;
        }
    }

    /**
     * @param integer $value 设置最大日志文件数
     */
    public function setMaxLogFiles($value)
    {
        if (($this->_maxLogFiles = (int)$value)  < 1) {
            $this->_maxLogFiles = 1;
        }
    }
    /**
     * warn
     * @param string|array $message 日志信息
     * @param string $category 日志分类
     * @return bool
     */
    public static function warn($message, $category = '-')
    {
        return self::write($message, self::LEVEL_WARN, $category);
    }
    /**
     * info
     * @param string|array $message 日志信息
     * @param string $category 日志分类
     * @return bool
     */
    public static function info($message, $category = '-')
    {
        return self::write($message, self::LEVEL_INFO, $category);
    }
    /**
     * error
     * @param string|array $message 日志信息
     * @param string $category 日志分类
     * @return bool
     */
    public static function error($message, $category = '-')
    {
        return self::write($message, self::LEVEL_ERROR, $category);
    }
    /**
     * fatal
     * @param $message
     * @param string $category
     * @return bool
     */
    public static function fatal($message, $category = '-')
    {
        return self::write($message, self::LEVEL_FATAL, $category);
    }
    /**
     *  debug
     * @param string|array $message 日志信息
     * @param string $category 日志分类
     * @return bool
     */
    public static function debug($message, $category = '-')
    {
        return self::write($message, self::LEVEL_DEBUG, $category);
    }
    /**
     * 写入日志消息
     * @param string|array $message 日志信息
     * @param string $level 日志等级
     * @param string $category 日志分类
     * @return bool
     */
    public static function write($message, $level = self::LEVEL_INFO, $category = '-')
    {
        $obj = self::getInstance();
        //按日志级别记录日志
        if (! in_array($level, $obj->_levels)) {
            return false;
        }
        $obj->_logs[] = $obj->getLogInfo($message, $level, $category);
        $obj->_logCount++;
        if ($obj->autoFlush > 0 && $obj->_logCount >= $obj->autoFlush) {  //日志行数
            $obj->flush();
        } elseif (intval(memory_get_usage()/1024) >= $obj->_maxFileSize) {  //日志内存数
            $obj->flush();
        }
        return $obj;
    }

    /**
     * 获取日志前缀
     * @param $message
     * @param $level
     * @param $category
     * @param $timestamp
     * @return mixed|string
     */
    public function getMessagePrefix($message, $level, $category, $timestamp)
    {
        if ($this->_prefix !== null) {
            return call_user_func($this->_prefix, $message, $level, $category, $timestamp);
        }
        $defaultTemplate['%T'] = $this->getTime($timestamp);
        $defaultTemplate['%L'] = $this->getLevelName($level);
        $defaultTemplate['%P'] = $this->getProcessId();
        $defaultTemplate['%Q'] = $this->getRequestId();
        $defaultTemplate['%I'] = $this->getClientIp();
        $defaultTemplate['%C'] = $category ?? '-';
        $defaultTemplate['%S'] = '-';
        $template = [];
        $defaultTemplateArr = explode($this->_separator, $this->_defaultTemplate);

        foreach ($defaultTemplateArr as $v) { //按日志模板排序
            $template[$v] = $defaultTemplate[$v] ?? '';
        }

        return join($this->_separator, $template) . $this->_separator;
    }

    public function getLevelName($level)
    {
        if (empty($this->_levelName)) {
            $this->setLevelName(strtoupper($level));
        }
        return $this->_levelName;
    }

    public function getProcessId()
    {
        if (empty($this->_processId)) {
            $this->setProcessId(getmypid());
        }
        return $this->_processId;
    }

    public function getRequestId()
    {
        if (empty($this->_requestId)) {
            $this->setRequestId(uniqid());
        }
        return $this->_requestId;
    }

    public function getHostName()
    {
        if (empty($this->_hostName)) {
            $this->setHostName(gethostname());
        }
        return $this->_hostName;
    }

    public function getClientIp()
    {
        if (empty($this->_clientIp)) {
            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                return $_SERVER['HTTP_X_REAL_IP'];
            } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else if (!empty($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
            }
        } else
        {
            return $this->_clientIp;
        }
    }

    /**
     * 写入日志。
     */
    public function flush()
    {
        if ($this->_logCount > 0) {
            $this->onFlush();
            $this->_logs = [];
            $this->_logCount = 0;
        }
    }
    public function onFlush()
    {
        $this->processLogs($this->_logs);
    }

    /**
     * 格式化日志信息
     * @param array $logs
     * @return string
     */
    protected function formatMessage(array $logs)
    {
        list($message, $level, $category, $timestamp, $traces) = $logs;
        if (!is_string($message)) {
            if ($message instanceof \Throwable || $message instanceof \Exception) {
                $message = (string) $message;
            } else {
                $message = $this->export($message);
            }
        }
        $stack = [];
        if (!empty($traces)) {
            foreach ($traces as $trace) {
                if (isset($trace['file']) && isset($trace['line'])) {
                    $stack[] = "in {$trace['file']}:{$trace['line']}";
                }
            }
        }
        $prefix = $this->getMessagePrefix($message, $level, $category, $timestamp);

        return   " {$prefix} $message "
            . (empty($stack) ? '' : "\n    " . implode("\n    ", $stack));
    }
    /**
     * 数组对象转成字符串
     * @param mixed $var
     * @return string
     */
    private function export($var)
    {
        return var_export($var, true);
    }
    /**
     * 保存日志信息到文件
     * @param array $logs      日志信息
     * @throws Exception
     */
    protected function processLogs(array $logs)
    {
        $logFile =  $this->getLogFile();
        try {
            if (!is_file($logFile)) {
                touch($logFile);
            }
            $text = implode("\n", array_map([$this, 'formatMessage'], $logs)) . "\n";
            if ($this->_enableRotation && @filesize($logFile) > ($this->getMaxFileSize() * 1024)) {
                $this->rotateFiles();
            }
            if (($fp = @fopen($logFile, 'a')) === false) {
                throw new Exception("Unable to append to log file: {$logFile}");
            }
            @fwrite($fp, $text);
            fclose($fp);
        } catch (Exception $e) {
            throw new Exception('logException:'.$e->getMessage());
        }
    }
    /**
     * 分割文件
     */
    protected function rotateFiles()
    {
        $logFile =  $this->getLogFile();
        $max  = $this->getMaxLogFiles();
        for ($i = $max; $i > 0; --$i) {
            $rotateFile = $logFile . '.' . $i;
            if (is_file($rotateFile)) {
                if ($i === $max) {
                    unlink($rotateFile);
                } else {
                    rename($rotateFile, $logFile.'.'.($i+1));
                }
            }
        }
        if (is_file($logFile)) {
            rename($logFile, $logFile.'.1');
        }
    }
    /**
     * 生成文件名、行号和函数名
     * @param $message
     * @param string $level
     * @param string $category
     * @return array
     */
    protected function getLogInfo($message, $level = 'info', $category = '-')
    {
        $time = microtime(true);
        $traces = [];
        $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_pop($ts);
        $count = 0;
        foreach ($ts as $trace) {
            if (isset($trace['file'], $trace['line']) &&  strpos($trace['file'], __FILE__) !== 0) {
                unset($trace['object'], $trace['args']);
                $traces[] = $trace;
                if (++$count >= $this->_traceLevel) {
                    break;
                }
            } elseif (!isset($trace['file'], $trace['line'])) {
                $traces[] = $trace;
                if (++$count >= $this->_traceLevel) {
                    break;
                }
            }
        }
        $category = $this->getCategory($category, $traces);
        if ($this->_traceLevel === 0) {
            $traces = [];
        }
        return  [$message, $level, $category, $time, $traces];
    }

    protected function getCategory($category, $traces)
    {
        if ($category !== '-') {
            return $category;
        }
        if ($this->_traceLevel === 0) {
            if (isset($traces[0]['file'])) {
                $pathArr = pathinfo($traces[0]['file']);
                $category = $pathArr['basename'] . ':' .  $traces[0]['line'];
            }
        } else {
            if (!empty($traces[1])) {
                $category = '';
                if (isset($traces[1]['class'])) {
                    $category .= $traces[1]['class'];
                }
                if (isset($traces[1]['type'])) {
                    $category .= $traces[1]['type'];
                }
                if (isset($traces[1]['function'])) {
                    $category .= $traces[1]['function'];
                }
            }
        }
        return $category;
    }

    /**
     * 显示日期时间格式
     * @param null $timestamp 需要格式化的时间戳
     * @return string  格式化的日期
     * @since 2.0.0
     */
    protected function getTime($timestamp)
    {
        $timestamp = str_replace(',', '.', (string) $timestamp);
        $parts = explode('.', $timestamp);
        return date('Y-m-d H:i:s', $parts[0]) . ($this->_microtime && isset($parts[1]) ? ('.' . sprintf('%04d', $parts[1])) : '');
    }
}

