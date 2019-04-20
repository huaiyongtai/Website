<?php

/**
 * 错误日志处理类
 *
 */

namespace DevSxe\Lib;

class MyErrors
{

    /**
     * 保存所有错误堆栈
     * @var array
     */
    private static $_stacks = [];

    /**
     * 启用自定义错误处理
     */
    public static function run()
    {
        restore_error_handler();
        set_error_handler(array(__CLASS__, 'userErrorHandler'));
    }

    /**
     * 获取所有错误信息
     *
     * return array
     */
    public static function getAllInfos()
    {
        return static::$_stacks;
    }

    /**
     * 用户自定义错误处理函数
     *
     * @param string $errno 错误号
     * @param string $errmsg 错误信息
     * @param string $errfile 错误文件
     * @param string $errline 错误文件行号
     * @param string $errcontext 错误上下文信息
     */
    public static function userErrorHandler($errno, $errmsg, $errfile, $errline, $errcontext)
    {
        $config = \DevSxe\Lib\R('\DevSxe\Application\Config\LogPath');

        if (isset($config['print']) && $config['print'] == true) {
            /**
             * 定义错误字符串的关联数组
             * 在这里我们只考虑
             * E_WARNING, E_NOTICE, E_USER_ERROR,
             * E_USER_WARNING 和 E_USER_NOTICE
             */
            $errortype = array(
                E_ERROR => 'Error',
                E_WARNING => 'Warning',
                E_PARSE => 'Parsing Error',
                E_NOTICE => 'Notice',
                E_CORE_ERROR => 'Core Error',
                E_CORE_WARNING => 'Core Warning',
                E_COMPILE_ERROR => 'Compile Error',
                E_COMPILE_WARNING => 'Compile Warning',
                E_USER_ERROR => 'User Error',
                E_USER_WARNING => 'User Warning',
                E_USER_NOTICE => 'User Notice',
                E_STRICT => 'Runtime Notice',
                E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
            );

            $err = array();
            $err['datetime'] = date('Y-m-d H:i:s');
            $err['errno'] = $errno;
            $err['errtype'] = $errortype[$errno];
            $err['errmsg'] = $errmsg;
            $err['errfile'] = $errfile;
            $err['errline'] = $errline;
            $err['errcontext'] = $errcontext;
            $err['debugtrace'] = debug_backtrace();

            array_unshift(static::$_stacks, $err);
        }

        if (class_exists('\DevSxe\Service\Log\DevSxeLog')) {
            $mes = self::getLogMes($errmsg);
            switch ($errno) {
                case E_ERROR:
                    \DevSxe\Service\Log\DevSxeLog::error($mes, $errfile, $errline);
                    break;
                case E_WARNING:
                    \DevSxe\Service\Log\DevSxeLog::warning($mes, $errfile, $errline);
                    break;
                case E_NOTICE:
                    \DevSxe\Service\Log\DevSxeLog::notice($mes, $errfile, $errline);
                    break;
                case E_USER_ERROR:
                    \DevSxe\Service\Log\DevSxeLog::error($mes, $errfile, $errline);
                    break;
                case E_USER_WARNING:
                    \DevSxe\Service\Log\DevSxeLog::warning($mes, $errfile, $errline);
                    break;
                case E_USER_NOTICE:
                    \DevSxe\Service\Log\DevSxeLog::notice($mes, $errfile, $errline);
                    break;
            }
        }
    }

    public static function getLogMes($message)
    {
        $delimiter = chr(30) . ' ';
        $mes = '';

        $arrKeys = array('message');
        if (isset(Core::$data['params']['debug']) && Core::$data['params']['debug'] == 2) {
            $debugtrace = self::debug_string_backtrace();
            array_push($arrKeys, 'debugtrace');
        }
        foreach ($arrKeys as $key) {
            $mes .= '[' . $key . '] => ' . str_replace("\n", ' ', $$key) . $delimiter;
        }
        $mes = rtrim($mes, $delimiter);

        return $mes;
    }

    private static function debug_string_backtrace()
    {
        ob_start();
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = ob_get_contents();
        ob_end_clean();

        //是否要删除无用调用信息
        $traceArr = array_slice(explode("\n", $trace), 3);
        $trace = implode(' ', $traceArr);

        return $trace;
    }
}
