<?php
/**
 * @author  wxxiong <wxxiong6@gmail.com>
 *
 * @version v3.0.0
 *
 * @see    https://github.com/wxxiong6/wxxlogger/blob/master/README.md
 */

namespace wxxiong6\logger;

use Exception;

/**
 * Class logger.
 *
 * * @example
 * <pre>
 *   $config = [
 *      'logPath' => __DIR__.'/runtime/logs',
 *      'maxLogFiles' => 5,
 *      'traceLevel'  => 0,
 *      'maxFileSize' => 10240,
 *      'logFile'     => 'app'.date("ymd").'.log',
 *      'levels'      => ['error','warning', 'alert', 'info', 'debug'],
 *    ];
 *    logger::getInstance()->setConfig($config);
 *    logger::error(['mes'=>'error','code'=>100], '123123');
 *    logger::debug('debug');
 *      ...
 * </pre>
 */
class Logger
{
    /**
     * 系统不可用.
     */
    const LEVEL_EMERGENCY = 0x01;
    /**
     * 必须立刻处理
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下.
     */
    const LEVEL_ALERT = 0x02;
    /**
     *  紧急情况
     *  简单地说就是服务死掉了。
     */
    const LEVEL_CRITICAL = 0x03;
    /**
     *  运行时出现的错误
     *  不需要立刻采取行动，但必须记录下来后处理。
     */
    const LEVEL_ERROR = 0x04;
    /**
     * 出现非错误性的异常
     * 例如：使用了被弃用的API 、错误地使用了API或者非预想的不必要错误。
     */
    const LEVEL_WARN = 0x05;
    /**
     * 一般性重要的事件.
     */
    const LEVEL_NOTICE = 0x06;
    /**
     *  重要事件
     *  例如某项业务处理完毕，用户登录和SQL记录.
     */
    const LEVEL_INFO = 0x07;
    /**
     * 详情
     * 例如函数返回值的验证等等。业务相关的请用info.
     */
    const LEVEL_DEBUG = 0x08;
    /**
     * 在将日志从内存中输出到目标之前，应该记录多少消息。
     *
     * @var int
     */
    public $autoFlush = 1000;
    /**
     * 日志前缀
     */
    private $_prefix;
    /**
     * 记录日志级别,小写日志级别字母.
     *
     * @var array
     */
    private $_levels = [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug',
            ];
    /**
     * @var array 日志信息
     */
    private $_logs = [];
    /**
     * @var int 数量的日志消息
     */
    private $_logCount = 0;
    /**
     * 建议设置大于2，否则category无法自动显示.
     *
     * @var int 限制返回堆栈帧的数量
     */
    private $_traceLevel = 2;
    /**
     * 日志文件的大小.
     *
     * @var int
     */
    private $_maxFileSize = 1024; // in KB
    /**
     * @var int 最大日志文件数
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
     * @var bool 是否切割日志文件
     *
     * @since 2.0.0
     */
    private $_enableRotation = false;
    /**
     * @var bool 开启毫秒.
     *           Defaults to false.
     *
     * @since 2.0.0
     */
    private $_microtime = true;
    private $_useMemory;
    /**
     * @var string
     *             %L - Level 日志级别。
     *             %T - DateTime 如2019-12-17 19:17:02
     *             %Q - RequestId 区分单次请求，如没有调用setRequestId($string)方法，则在初始化请求时，采用内置的uniqid()方法生成的惟一值。
     *             %H - HostName 主机名。
     *             %P - ProcessId 进程ID。
     *             %I - Client IP 来源客户端IP; Cli模式下为local。取值优先级为：HTTP_X_REAL_IP > HTTP_X_FORWARDED_FOR > REMOTE_ADDR
     *             %C - Class::Action 类名::方法名，如UserService::getUserInfo。不在类中使用时，记录函数名
     *             %S - 占位符，什么都不做
     */
    private $_defaultTemplate = '%T|%L|%P|%I|%Q|%C';
    /**
     * @var string 分隔符，必须与$defaultTemplate 一致
     */
    private $_separator = '|';
    /**
     * @var static
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
        if (0 === strpos($name, 'set')) {
            $property = str_replace('set', '', $name);
            $property = '_'.lcfirst($property);
            if (isset($arguments[0])) {
                $this->$property = $arguments[0];
            }
        } elseif (0 === strpos($name, 'get')) {
            $property = str_replace('get', '', $name);
            $property = '_'.lcfirst($property);

            return $this->$property;
        }
    }

    /**
     * 设置配置.
     * @param array $config
     */
    public function setConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $func = 'set'.ucfirst($key);
            \call_user_func_array([__CLASS__, $func], [$val]);
        }
    }

    /**
     * 获取对象
     *
     * @return static
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
            register_shutdown_function(function () {
                self::$_instance->flush();
                register_shutdown_function([self::$_instance, 'flush'], true);
            });
        }

        return self::$_instance;
    }

    /**
     *  设置日志目录.
     *
     * @param string $logPath 日志目录
     *
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
        return $this->_logPath.\DIRECTORY_SEPARATOR.$this->_logFile;
    }

    /**
     * @param int $value maximum log file size in kilo-bytes (KB)
     */
    public function setMaxFileSize($value)
    {
        if (($this->_maxFileSize = (int) $value) < 1) {
            $this->_maxFileSize = 1;
        }
    }

    /**
     * @param int $value 设置最大日志文件数
     */
    public function setMaxLogFiles($value)
    {
        if (($this->_maxLogFiles = (int) $value) < 1) {
            $this->_maxLogFiles = 1;
        }
    }

    /**
     * warn.
     *
     * @param string|array $message  日志信息
     * @param string       $category 日志分类
     *
     * @return bool
     */
    public static function warn($message, $category = '-')
    {
        return self::getInstance()->write($message, self::LEVEL_WARN, $category);
    }

    /**
     * info.
     *
     * @param string|array $message  日志信息
     * @param string       $category 日志分类
     *
     * @return bool
     */
    public static function info($message, $category = '-')
    {
        return self::getInstance()->write($message, self::LEVEL_INFO, $category);
    }

    /**
     * error.
     *
     * @param string|array $message  日志信息
     * @param string       $category 日志分类
     *
     * @return bool
     */
    public static function error($message, $category = '-')
    {
        return self::getInstance()->write($message, self::LEVEL_ERROR, $category);
    }

    /**
     * alert.
     *
     * @param $message
     * @param string $category
     *
     * @return bool
     */
    public static function alert($message, $category = '-')
    {
        return self::getInstance()->write($message, self::LEVEL_ALERT, $category);
    }

    /**
     *  debug.
     *
     * @param string|array $message  日志信息
     * @param string       $category 日志分类
     *
     * @return bool
     */
    public static function debug($message, $category = '-')
    {
        return self::getInstance()->write($message, self::LEVEL_DEBUG, $category);
    }

    /**
     * 写入日志消息.
     *
     * @param string|array $message  日志信息
     * @param int          $level    日志等级
     * @param string       $category 日志分类
     *
     * @return bool
     */
    public function write($message, $level = self::LEVEL_INFO, $category = '-')
    {
        //按日志级别记录日志
        $levelName = $this->getLevelName($level);
        if (!\in_array(strtolower($levelName), $this->_levels)) {
            return false;
        }
        $this->_logs[] = $this->getLogInfo($message, $levelName, $category);
        ++$this->_logCount;
        if ($this->autoFlush > 0 && $this->_logCount >= $this->autoFlush) {  //日志行数
            $this->flush();
        }
    }

    /**
     * 获取日志前缀
     *
     * @param $message
     * @param $level
     * @param $category
     * @param $timestamp
     *
     * @return mixed|string
     */
    public function getMessagePrefix($message, $level, $category, $timestamp)
    {
        if (null !== $this->_prefix) {
            return \call_user_func($this->_prefix, $message, $level, $category, $timestamp);
        }
        $defaultTemplate['%T'] = $this->getTime($timestamp);
        $defaultTemplate['%L'] = $level;
        $defaultTemplate['%P'] = $this->getProcessId();
        $defaultTemplate['%Q'] = $this->getRequestId();
        $defaultTemplate['%I'] = $this->getClientIp();
        $defaultTemplate['%C'] = $category ?: '-';
        $defaultTemplate['%S'] = '-';
        $template = [];
        $defaultTemplateArr = explode($this->_separator, $this->_defaultTemplate);
        foreach ($defaultTemplateArr as $v) { //按日志模板排序
            $template[$v] = isset($defaultTemplate[$v]) ? $defaultTemplate[$v] : '';
        }

        return implode($this->_separator, $template).$this->_separator;
    }

    public function getLevelName($level)
    {
        $levelNames = [
                    self::LEVEL_EMERGENCY => 'EMERGENCY',
                    self::LEVEL_ALERT => 'ALERT',
                    self::LEVEL_CRITICAL => 'CRITICAL',
                    self::LEVEL_ERROR => 'ERROR',
                    self::LEVEL_WARN => 'WARNING',
                    self::LEVEL_NOTICE => 'NOTICE',
                    self::LEVEL_INFO => 'INFO',
                    self::LEVEL_DEBUG => 'DEBUG',
                ];

        return isset($levelNames[$level]) ? $levelNames[$level] : 'UNKNOWN';
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
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                return $_SERVER['REMOTE_ADDR'];
            }
        } else {
            return $this->_clientIp;
        }
    }

    /**
     * 写入日志。
     */
    public function flush()
    {
        if ($this->_logCount > 0) {
            $this->onFlushFile($this->_logs);
            unset($this->_logs);
            $this->_logs = [];
            $this->_logCount = 0;
        }
    }

    /**
     * 格式化日志信息.
     *
     * @return string
     */
    protected function formatMessage(array $logs)
    {
        list($message, $level, $category, $timestamp, $traces) = $logs;
        if (!\is_string($message)) {
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

        return   "{$prefix} $message"
                    .(empty($stack) ? '' : "\n".implode("\n", $stack));
    }

    /**
     * 保存日志信息到文件.
     *
     * @param array $logs 日志信息
     *
     * @throws Exception
     */
    protected function onFlushFile(array $logs)
    {
        $logFile = $this->getLogFile();
        $text = implode("\n", array_map([$this, 'formatMessage'], $logs))."\n";
        if (false === ($fp = @fopen($logFile, 'a'))) {
            throw new Exception("Unable to append to log file: {$logFile}");
        }

        try {
            @flock($fp, LOCK_EX);
            if ($this->_enableRotation) {
                clearstatcache();
            }
            if ($this->_enableRotation && @filesize($logFile) > ($this->getMaxFileSize() * 1024)) {
                $this->rotateFiles();
                @flock($fp, LOCK_UN);
                @fclose($fp);
                $writeResult = @file_put_contents($logFile, $text, FILE_APPEND | LOCK_EX);
                if (false === $writeResult) {
                    $error = error_get_last();
                    throw new \RuntimeException("Unable to export log through file!: {$error['message']}");
                }
                $textSize = \strlen($text);
                if ($writeResult < $textSize) {
                    throw new \RuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
                }
            } else {
                $writeResult = @fwrite($fp, $text);
                if (false === $writeResult) {
                    $error = error_get_last();
                    throw new \RuntimeException("Unable to export log through file!: {$error['message']}");
                }
                $textSize = \strlen($text);
                if ($writeResult < $textSize) {
                    throw new \RuntimeException("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
                }
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        } catch (Exception $e) {
            if (false === ($fp = @fopen($logFile, 'w+'))) {
                @flock($fp, LOCK_UN);
                @fclose($fp);
            }
        }
    }

    /**
     * 分割文件.
     */
    protected function rotateFiles()
    {
        $logFile = $this->getLogFile();
        $logPathInfo = pathinfo($logFile);
        $logFileExt = $logPathInfo['extension'];
        $max = $this->getMaxLogFiles();
        $extLen = \strlen($logFileExt);
        $newLogFile = substr($logFile, 0, -$extLen).'%d.'.$logFileExt;
        for ($i = $max; $i >= 0; --$i) {
            $rotateFile = (0 === $i) ? $logFile : sprintf($newLogFile, $i);
            if (is_file($rotateFile)) {
                if ($i === $max) {
                    @unlink($rotateFile);
                    continue;
                }
                $newLogFile = sprintf($newLogFile, $i + 1);
                @copy($rotateFile, $newLogFile);
                if (0 === $i) {
                    $this->clearLogFile($rotateFile);
                }
            }
        }
    }

    /**
     * 生成日志信息.
     *
     * @param $message
     * @param string $level
     * @param string $category
     *
     * @return array
     */
    protected function getLogInfo($message, $level, $category = '-')
    {
        $time = microtime(true);
        $traces = [];
        if ($this->_traceLevel > 0) {
            $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_pop($ts); //删除入口脚本
            $count = 0;
            foreach ($ts as $trace) {
                if (isset($trace['file'], $trace['line']) && 0 !== strpos($trace['file'], __FILE__)) {
                    unset($trace['object'], $trace['args']);
                    $traces[] = $trace;
                    if (++$count >= $this->_traceLevel) {
                        break;
                    }
                }
            }
            $category = $this->getCategory($category, $traces);
        }

        return [$message, $level, $category, $time, $traces];
    }

    /**
     * @param string $category
     * @param array  $traces
     *
     * @return string
     */
    protected function getCategory($category, $traces)
    {
        if ('-' !== $category) {
            return $category;
        }
        if (0 === $this->_traceLevel) {
            if (isset($traces[0]['file'])) {
                $pathArr = pathinfo($traces[0]['file']);
                $category = $pathArr['basename'].':'.$traces[0]['line'];
            }
        } elseif (!empty($traces[1])) {
            $traces[1]['class'] = isset($traces[1]['class']) ? $traces[1]['class'] : '';
            $traces[1]['type'] = isset($traces[1]['type']) ? $traces[1]['type'] : '';
            $traces[1]['function'] = isset($traces[1]['function']) ? $traces[1]['function'] : '';
            $category = $traces[1]['class'].$traces[1]['type'].$traces[1]['function'];
        }

        return $category;
    }

    /**
     * 显示日期时间格式.
     *
     * @param null $timestamp 需要格式化的时间戳
     *
     * @return string 格式化的日期
     *
     * @since 2.0.0
     */
    protected function getTime($timestamp)
    {
        $timestamp = str_replace(',', '.', (string) $timestamp);
        $parts = explode('.', $timestamp);

        return date('Y-m-d H:i:s', $parts[0]).($this->_microtime && isset($parts[1]) ? ('.'.sprintf('%04d', $parts[1])) : '');
    }

    /**
     * 数组对象转成字符串.
     *
     * @param mixed $var
     *
     * @return string
     */
    private function export($var)
    {
        return var_export($var, true);
    }

    private function clearLogFile($rotateFile)
    {
        if ($filePointer = @fopen($rotateFile, 'a')) {
            @ftruncate($filePointer, 0);
            @fclose($filePointer);
        }
    }
}
