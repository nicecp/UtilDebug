<?php
namespace UtilDebug;
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
     * E_USER_NOTICE
     * @var string
     */
    const DEBUG_NOTICE = '[ notice ]';

    /**
     * E_USER_WARNING
     * @var string
     */
    const DEBUG_WARNING = '[ warning ]';

    /**
     * E_USER_ERROR
     * @var string
     */
    const DEBUG_ERROR = '[ error ]';

    /*
     * 日志路径
     * @var string
     */
    private static $error_log = 'php_user_level_error.log';

    public static function notice($msg = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_NOTICE, $msg, $exp);
    }

    public static function warning($msg = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_WARNING, $msg, $exp);
    }

    public static function error($msg = "", \Exception $exp = null)
    {
        self::triggerError(self::DEBUG_ERROR, $msg, $exp);
    }

    /**
     * 设置文件名
     *
     * @param string $log
     */
    public static function config($log = "")
    {
        if (!strlen($log)) {
            return ;
        }

        self::$error_log = $log;
    }

    /**
     * 错误触发器
     * @param $level
     * @param $msg
     * @param $exp
     */
    protected static function triggerError($level, $msg, \Exception $exp)
    {
        if (!self::getLog()) {
            return ;
        }
        $msg = is_null($exp) ? $msg : sprintf("%s (%s: %s)", $msg, get_class($exp) ,$exp->getMessage());
        self::errorLog($level, self::stackTrace($msg, $exp));
    }

    /**
     * @param $level
     * @param array $error
     */
    protected static function errorLog($level, Array $error)
    {
        $message = sprintf("[%s] %s %s", date("d/m/Y H:i:s"), $level, array_shift($error));
        $stack_trace = implode("\n", $error);

        self::writeLog("{$message}\n{$stack_trace}\n");
    }

    /**
     * @param $error
     * @throws \Exception
     */
    protected static function writeLog($error)
    {
        $path = dirname(self::$error_log);
        if (!is_dir($path) || !is_writable($path)) {
            throw new \Exception("{$path} isn`t writable");
        }
        // 旧日志归档
        self::reNameLog();
        @file_put_contents(self::$error_log, $error, FILE_APPEND | LOCK_EX);

    }

    /**
     * 归档前一天日志
     */
    private static function reNameLog()
    {
        $mod_time = filemtime(self::$error_log);
        if (!$mod_time || $mod_time > strtotime(date('Y-m-d'))) {
            return ;
        }
        $new_name = dirname(self::$error_log)
            .'/'
            . basename(self::$error_log, '.log')
            . date('Ymd', strtotime("-1 day"))
            . '.log';
        rename(self::$error_log, $new_name);
    }

    /**
     * 是否开启日志
     * @return bool
     */
    private static function getLog()
    {
        if (!in_array(strtoupper(ini_get("display_errors")), array("ON","TRUE","1","STDOUT"))) {
            return false;
        }

        if (!is_file(self::$error_log)) {
            self::$error_log = dirname(ini_get("error_log")) . "/" . self::$error_log;
        }
        return true;
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
        if (isset($trace[0]['file'])) {
            $msg .= " in {$trace[0]['file']}: {$trace[0]['line']}";
        }

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
        array_walk($line['args'], function ($item, $key) use (&$param) {
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