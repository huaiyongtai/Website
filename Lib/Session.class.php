<?php

namespace DevSxe\Lib;

use \DevSxe\Lib\Config;

class Session
{

    private static $_memConn;

    public static function getConn()
    {

        if (empty(self::$_memConn)) {
            $mcConf = Config::get('\DevSxe\Application\Config\Storage\Default\mc');
            if (empty($mcConf)) {
                throw new \Exception('Session is not found');
            }

            self::$_memConn = new \Memcache();
            foreach ($mcConf['servers'] as $v) {
                self::$_memConn->addServer($v[0], $v[1], true, $v[2], 1, 15, $v[3]);
            }
        }

        return self::$_memConn;
    }

    /**
     * 获取session信息
     *
     * @param type $id  sessionId
     * @param type $isSerialize 是否需要反序列化，1则返回数组
     * @return type
     */
    public static function get($id, $isSerialize = 0)
    {

        self::getConn();

        $data = self::$_memConn->get($id);

        if ($isSerialize == 1 && !empty($data)) {
            $result = explode(';', trim($data, ';'));

            $data = array();
            foreach ($result as $val) {
                $res = explode('|', $val . ';');
                $data[$res[0]] = unserialize($res[1]);
            }
        }

        return $data;
    }

    /**
     * 写入session信息
     *
     * @param type $id  sessionId
     * @param type $data    数据
     * @param type $seconds 过期时间（秒）
     * @param type $isSerialize 是否需要序列化
     * @return type
     */
    public static function set($id, $data, $seconds, $isSerialize = 0)
    {

        self::getConn();

        if ($isSerialize == 1 && is_array($data)) {
            $str = '';
            foreach ($data as $key => $val) {
                $str .= $key . '|' . serialize($val);
            }

            $data = $str;
        }

        return self::$_memConn->set($id, $data, 0, $seconds);
    }

    /**
     * 删除session信息
     *
     * @param type $id  sessionId
     * @return type
     */
    public static function del($id)
    {

        self::getConn();

        return self::$_memConn->set($id, '', 0, 1);
    }

}
