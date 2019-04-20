<?php

/**
 * 核心类，保存类加载日志、错误日志、配置文件、各个模型等
 */

namespace DevSxe\Lib;

class Core
{

    /**
     * 日志
     *  'data'      执行结果
     *  'error'     错误信息
     *  'exception' 异常信息
     */
    public static $data = [];

    /**
     * 保存各种对象
     *
     * @var array
     */
    static public $objects = [];

    /**
     * 保存各种配置状态
     *
     * @static
     * @access public
     * @var array
     * @name $conf
     */
    static public $conf = array(
        /**
         * 错误日志记录控制
         *
         * 0:关闭错误日志
         * 1:打开错误记录
         */
        'error_log' => 2,

        /**
         * 请求日志记录
         *
         * 0:关闭错误日志
         * 1:打开错误记录
         */
        'log' => 2,

        /**
         * xhprof函数追踪开关
         */
        'xhprof' => 0,

        /**
         * xhprof Url地址
         */
        'xhprof_url' => '',

        /**
         * pdo日志开关
         * 0: 关闭
         * 1: 打开
         */
        'pdo' => 1,

        /**
         * 脚本分析开关
         * 0: 关闭
         * 1: 打开
         */
        'script_analysis' => 0,
    );

    /**
     * db引擎执行日志开关
     */
    static public $dbLog = array(
        /**
         * 数据库操作执行时间请求统计开关
         * 1：打开
         * 2：关闭
         */
        'pdo' => 1,
        'redis' => 2,
        'memcache' => 2,
        'mysql' => array('time' => 0, 'cnt' => 0)
    );

    /**
     * 执行时间统计
     */
    static public $time = array(
        'mysql' => array(
            'time' => 0,
            'cnt' => 0,
            'details' => array(),
        ),
        'system' => array(
            'begin' => 0,
            'end' => 0,
            'total' => 0,
        ),
        'redis' => array(
            'time' => 0,
            'cnt' => 0,
        ),
    );

    /**
     * 内存占用统计
     */
    static public $memory = array(
        'begin' => 0,
        'end' => 0,
        'mem_use' => 0,
        'mem_peak_use' => 0,
    );

    /**
     * 控制器，执行动作，参数
     */
    static public $c = array(
        'controller' => '',
        'method' => '',
        'params' => '',
    );

    /**
     * 业务端控制器，业务端执行动作
     */
    static public $cake_c = array(
        'controller' => '',
        'method' => '',
    );

    /**
     * cURL请求
     */
    static public $curl = [];
}
