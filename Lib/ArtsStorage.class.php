<?php

namespace DevSxe\Lib;

use DevSxe\Lib\Config;
use DevSxe\Lib\Cache;

class ArtsStorage
{

    static private $_dataModel;
    static private $_cache;
    static private $_db = [];

    /**
     * 获取数据模型
     *
     * @param type $dataModelName
     * @return boolean
     */
    private static function _getDataModel($dataModelName, $dataType = array())
    {

        //获取数据模型配置
        $dataModel = Config::get($dataModelName);

        if (empty($dataModel)) {
            throw new \Exception($dataModelName . ':dataModel no found 3001', 3001);
        }

        //类型验证
        if (!empty($dataType)) {
            if (!in_array($dataModel['type'], $dataType)) {
                throw new \Exception($dataModelName . ':dataModel-type error 3002', 3002);
            }

            if ($dataModel['type'] == 'hash') {
                if (empty($dataModel['cacheFields'])) {
                    throw new \Exception($dataModelName . ':dataModel-cacheFields error 3003', 3003);
                }
            }
        }

        //是否需要保存数据库
        if (!empty($dataModel['isSave'])) {
            if (empty($dataModel['dbName']) || empty($dataModel['dbFields']) || empty($dataModel['dbEngine'])) {
                throw new \Exception($dataModelName . ':dataModel-dbEngine error 3004', 3004);
            }
        }

        //self::$_cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', $dataModel['cacheEngine']);f
        self::$_dataModel = $dataModel;
    }

    /**
     * 获取数据的key
     *
     * @param type $id
     * @return boolean
     */
    private static function _getDataKey($id = '')
    {

        if (empty($id) && self::$_dataModel['paramNum'] == 0) {
            return self::$_dataModel['key'];
        }

        //参数是否正确
        $_keyId = explode(',', $id);
        if (count($_keyId) != self::$_dataModel['paramNum']) {
            throw new \Exception(self::$_dataModel['key'] . ':dataModel-paramNum error 3005', 3005);
        }

        $dataKey = vsprintf(self::$_dataModel['key'], $_keyId);

        return $dataKey;
    }

    private static function _getRedis($dataKey)
    {
        if(empty(self::$_dataModel['cacheEngine'])) {
            $engine = Config::get('\DevSxe\Application\Config\Storage\Default\artsredisconf');
            if(empty($engine['sharding'])) {
                $redis = reset($engine['server']);
            } else {
                #$sign = md5($dataKey);
                $sign = hash('tiger192,4',$dataKey);
                $inx = hexdec(substr($sign, -2));
                $redis = $engine['server'][$engine['sharding'][$inx]];
            }
            self::$_cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', $redis);
        } else {
            self::$_cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::$_dataModel['cacheEngine']);
        }

    }

    /**
     * 获取key的值，字符串型，允许多key操作
     *
     * @param type $dataModelName
     * @param type $ids
     * @return type
     */
    public static function get($dataModelName, $ids)
    {

        self::_getDataModel($dataModelName, array('string', 'incr'));

        if (!empty($ids)) {
            $ids = array_values($ids);
            foreach ($ids as $id) {
                $dataKeys[] = self::_getDataKey($id);
            }
        } else {
            $dataKeys[0] = self::_getDataKey();
        }

        self::_getRedis($dataKeys[0]);

        $result = self::$_cache->get($dataKeys);

        $data = array();
        foreach ($result as $key => $val) {

            $_key = !empty($ids) ? $ids[$key] : 0;
            $_key = (string) $_key;

            if (self::$_dataModel['type'] == 'string') {
                $data[$_key] = json_decode($val, true);
            } else {
                $data[$_key] = $val;
            }
        }

        return $data;
    }

    public static function zGet($dataModelName, $id, $start = 0, $end = -1, $sort = 1, $withscores = false)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        self::_getRedis($dataKey);

        return self::$_cache->zGet($dataKey, $start, $end, $sort, $withscores);
    }

    /**
     * 有序集合，单key下的元素总数
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $start
     * @param type $end
     * @return boolean
     */
    public static function zCount($dataModelName, $id, $start = 0, $end = 0)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        self::_getRedis($dataKey);

        $data = self::$_cache->zCount($dataKey, $start, $end);

        return !empty($data) ? $data : 0;
    }

    /**
     * 有序集合，单key下，某元素的score值
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $value
     * @return boolean
     */
    public static function zScore($dataModelName, $id, $value)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        self::_getRedis($dataKey);

        return self::$_cache->zScore($dataKey, $value);
    }

    public static function hGet($dataModelName, $id, $fields = array())
    {

        self::_getDataModel($dataModelName, array('hash'));

        if (!empty($fields)) {
            $fields = array_intersect($fields, self::$_dataModel['cacheFields']);
        }

        $dataKey = self::_getDataKey($id);
        self::_getRedis($dataKey);
        $result = self::$_cache->hGet($dataKey, $fields);

        return $result;
    }


}
