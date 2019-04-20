<?php

namespace DevSxe\Lib;

class Config
{

    /**
     * 保存所有配置文件集和
     *
     * @example
     * $_config = > array(
     *      'course' => array(
     *      ),
     *  )
     */
    static private $_config = array();

    /**
     * 获取一个配置信息
     *
     * $key 必须为一个命名空间下文件中，存在的一个键值
     *
     * 例如:获取下面文件中的某个存在的key
     *  /home/xxx/api/applicaiton/config/storage/defaut.class.php
     *
     *  config::get('\devsxe\application\config\storage\default\{key}');
     *
     * @param string $key
     *
     * return array
     */
    static public function get($key)
    {
        $offset = strrpos($key, '\\', 2);
        $dir = substr($key, 0, $offset);
        $key = substr($key, $offset + 1);

        if (!isset(self::$_config[$dir])) {
            self::$_config[$dir] = \DevSxe\Lib\R($dir);
        }

        return isset(self::$_config[$dir][$key]) ? self::$_config[$dir][$key] : false;
    }

    static public function getAllConfig()
    {
        return self::$_config;
    }

}
