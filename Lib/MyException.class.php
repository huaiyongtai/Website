<?php

/**
 * 自定义一个异常处理类
 *
 */

namespace DevSxe\Lib;

class MyException
{

    /**
     * 保存所有错误堆栈
     * @var array
     */
    private static $_stacks = [];

    /**
     * 开启自定义异常处理
     */
    public static function run()
    {
        set_exception_handler(array(__CLASS__, 'userExceptionHandler'));
    }

    /**
     * 获取所有异常信息
     * return array
     */
    public static function getAllInfos()
    {
        return static::$_stacks;
    }

    /**
     * 用户自定义异常错误
     *
     * @param Exception object $exception
     */
    public static function userExceptionHandler($exception)
    {
        $config = \DevSxe\Lib\R('\DevSxe\Application\Config\LogPath');
        if (isset($config['print']) && $config['print'] == true) {
            $exceptions = array();
            $exceptions['datetime'] = date('Y-m-d H:i:s');
            $exceptions['exceptionMessage'] = $exception->getMessage();
            $exceptions['exceptionCode'] = $exception->getCode();
            $exceptions['scriptname'] = $exception->getFile();
            $exceptions['scriptnum'] = $exception->getLine();
            $exceptions['trace'] = $exception->getTraceAsString();

            array_unshift(static::$_stacks, $exceptions);
        }

        if (class_exists('\DevSxe\Service\Log\DevSxeLog')) {
            $delimiter = chr(30) . ' ';
            $mes = '';
            $mes .= '[message] => ' . str_replace("\n", ' ', $exception->getMessage()) . $delimiter;
            $mes .= '[debugtrace] => ' . str_replace("\n", ' ', $exception->getTraceAsString()) . $delimiter;
            //当为PDO异常时拼接上一条执行的sql语句
            if ($exception instanceof \PDOException) {
                if (!empty(Core::$time['mysql']['details'])) {
                    $query = end(Core::$time['mysql']['details']);
                    $mes .= '[lastsql] => ' . str_replace(array("\r", "\n"), ' ', $query['sql']) . $delimiter;
                }
            }
            $mes = rtrim($mes, $delimiter);

            \DevSxe\Service\Log\DevSxeLog::error($mes, $exception->getFile(), $exception->getLine());
        }
        return;
    }

}
