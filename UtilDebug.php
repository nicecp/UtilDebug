<?php
namespace UtilDebug;
use Workerman\Worker;
/**
 * Class UtilDebug
 * 不同于PHP错误日志,此为用户级别错误,日志文件与fpm分开
 * 由开发人员自己调用
 *
 * @attention a. 当系统日志开启时才会记录日志; b. 与fpm日志保持同一级目录; c. 凌晨归档旧日志
 * @author null
 * @date 8/15/2017
 */
class UtilDebug {

    /**
     * E_USER_MESSAGE
     * @var string
     */
    const DEBUG_MESSAGE = '[ MESSAGE ]';
    /**
     * E_USER_NOTICE
     * @var string
     */
    const DEBUG_NOTICE = '[ NOTICE ]';

    /**
     * E_USER_WARNING
     * @var string
     */
    const DEBUG_WARNING = '[ WARNING ]';

    /**
     * E_USER_ERROR
     * @var string
     */
    const DEBUG_ERROR = '[ ERROR ]';

    /**
     * 最后一条错误信息
     * @var string
     */
    private static $lastError = 'none';

    /**
     * 是否使用PHP系统日志
     * @var bool
     */
    private static $sysLogSwitch = true;

    /**
     * 进程号
     * @var int
     */
    private static $pid = "";

    /*
     * 日志路径
     * @var string
     */
    private static $errorLog = array(
        'success' => 'success.log',
        'error'   => 'error.log'
    );

    /**
     * @param string $msg 记录信息
     * @param string $oob 额外储存的数据
     */
    public static function message($msg = "", $oob = "")
    {
        self::triggerMessage(self::DEBUG_MESSAGE, $msg, $oob);
    }

    /**
     * @param string $msg
     * @param string $oob
     * @param \Exception|null $exp
     */
    public static function notice($msg = "", $oob = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_NOTICE, $msg, $oob, $exp);
    }

    /**
     * @param string $msg
     * @param string $oob
     * @param \Exception|null $exp
     */
    public static function warning($msg = "", $oob = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_WARNING, $msg, $oob, $exp);
    }

    /**
     * @param string $msg
     * @param string $oob
     * @param \Exception|null $exp
     */
    public static function error($msg = "", $oob = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_ERROR, $msg, $oob, $exp);
    }

    /**
     * 设置文件名
     *
     * @param string $log
     */
    public static function config($type = "", $log = "")
    {
        if (!strlen($log) || !strlen($type)) {
            throw new \Exception("Log conf should not be empty either.");
        }

        self::$errorLog[$type] = $log;
    }

    public static function triggerMessage($level, $message, $oob)
    {
        $message  = $message . ' with out-of-band ' . $oob;
        self::messageLog($level, $message);
    }

    protected static function messageLog($level, $message)
    {
        $message = is_array($message) ? $message : array($message);
        self::writeLog(self::format($level, $message), self::getLog('success'));
    }

    /**
     * 错误触发器
     * @param $level
     * @param $msg
     * @param $exp
     */
    protected static function triggerError($level, $msg, $oob, \Exception $exp = null)
    {
	$message = is_null($exp) ? '' : sprintf("%s in %s on line %d", $exp->getMessage(), $exp->getFile(), $exp->getLine());
        $msg = "{$msg} {$message} with out-of-band {$oob}";
        self::errorLog($level, self::stackTrace($msg, $exp));
    }
    /**
     * @param $level
     * @param array $error
     */
    protected static function errorLog($level, Array $error)
    {
        self::$lastError = self::format($level, $error);
        self::writeLog(self::$lastError,  self::getLog('error'));
    }

    /**
     * 格式化日志
     * @param $level
     * @param $error
     * @return string
     */
    private static function format($level, $error)
    {
        $prefix = sprintf("[%s %d]", date('Y-m-d H:i:s'), self::getCurrentPid());
        $message = sprintf("%s %s %s", $prefix, $level, array_shift($error));
        $errorStack = array_combine(array_keys($error), array_map(function($log) use ($prefix) {
            return $prefix . $log;
        }, $error));

        return $message . "\n" . (empty($error) ? "" : implode("\n", $errorStack) . "\n");
    }
    /**
     * @param $error
     * @throws \Exception
     */
    protected static function writeLog($error, $logFile)
    {
        $path = dirname($logFile);
        if (!is_dir($path) || !is_writable($path)) {
            throw new \Exception("{$path} isn`t writable or not exist.");
        }
        // 旧日志归档
        self::reNameLog($logFile);
        file_put_contents($logFile, $error, FILE_APPEND | LOCK_EX);
        if (!Worker::$daemonize) {
            echo $error;
        }
    }

    /**
     * 归档前一天日志
     */
    private static function reNameLog($logFile)
    {
        if (!file_exists($logFile)) {
            return ;
        }
        $mod_time = filemtime($logFile);
        if (!$mod_time || $mod_time > strtotime(date('Y-m-d'))) {
            return ;
        }
        $new_name = dirname($logFile)
                    .'/'
                    . basename($logFile, '.log')
                    . date('Ymd', strtotime("-1 day"))
                    . '.log';
        rename($logFile, $new_name);
    }

    /**
     * 获取当前进程号
     * @return int
     */
    private static function getCurrentPid()
    {
        if (self::$pid <= 0) {
            self::$pid = posix_getpid();
        }
        return self::$pid;
    }

    public static function setSysLog($switch)
    {
        if (!is_bool($switch)) {
            throw new \Exception("Param should be bool.");
        }

        self::$sysLogSwitch = $switch;
    }
    /**
     * 获取日志路径
     * @return bool
     */
    private static function getLog($type)
    {
        if (!self::$sysLogSwitch || !strcmp($type, 'error') === 0) {
            return self::$errorLog[$type];
        }
        return ini_get("error_log");
    }

    public static function getLastError()
    {
        return self::$lastError;
    }

    /**
     * 调用栈
     *
     * @param \Exception $exp
     * @return array
     */
    public static function stackTrace($msg,\Exception $exp = null)
    {

        $trace = self::getTrace($exp);
        $debug = array($msg, "Stack trace:");
        array_walk($trace, function ($line, $key) use (&$debug) {
            $debug[] = "#{$key} " . self::getStraceString($line);
        });

        return $debug;
    }

    /**
     * @param \Exception|null $exp
     * @return array
     */
    private static function getTrace($exp)
    {
        return !is_null($exp) ? $exp->getTrace() : array_slice(debug_backtrace(), 2);
    }

    /**
     * 将每一条调用栈转换成字符串
     *
     * @param array $line
     * @return string
     */
    private static function getStraceString($line)
    {
        $str =  sprintf(
            "%s(%s):%s%s%s",
            isset($line['file'])  ? $line['file']  : "[internal function]",
            isset($line['line'])  ? $line['line']  : '',
            isset($line['class']) ? $line['class'] : '',
            isset($line['type'])  ? $line['type']  : '',
            $line['function']
        );

        if (empty($line['args'])) {
            return $str . "()";
        }

        $param = "('";
        array_walk($line['args'], function ($item, $ignore) use (&$param) {
            switch (gettype($item)) {
                case 'array':
                    $param .= "Array', '";
                    break;
                case 'object':
                    $param .= get_class($item) . "', '";
                    break;
                case 'integer':
                    $param .= "{$item}', '";
                    break;
		case 'resource (closed)':
                case 'resource':
                    $param .= "{$item}', '";
                    break;
                default:
                    $param = $param . (strlen($item) <= 50 ? $item : substr($item, 0, 50)."...") . "', '";
                    break;
            }
        });

        return $str . substr($param, 0, -3) . ')';
    }
}

