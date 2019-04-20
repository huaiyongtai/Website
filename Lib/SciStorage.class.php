<?php

namespace DevSxe\Lib;

use DevSxe\Lib\Config;
use DevSxe\Lib\Cache;

class SciStorage
{

    static private $_dataModel;
    static private $_cache;
    public static $key;

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
        if (isset(Core::$data['params']['debug']) && Core::$data['params']['debug'] == 7) {
            self::$key = self::$_dataModel['key'];
        }

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

    private static function _getInstance($key = '')
    {
        //是否需要分库
        if (!empty(self::$_dataModel['sharding']) && is_array(self::$_dataModel['sharding']) && !empty($key)) {
            $machineNum = count(self::$_dataModel['sharding']);
            if ($machineNum <= 0) {
                throw new \Exception(self::$_dataModel['key'] . ':dataModel-dbEngine error 3014', 3014);
            }
            self::$_dataModel['cacheEngine'] = self::$_dataModel['sharding'][hexdec(substr(md5($key), -2)) % $machineNum];
        }
        self::$_cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::$_dataModel['cacheEngine']);
    }

    /**
     * 获取hash类型的单条数据，可以根据条件获取，条件为空时，获取该key的所有数据
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $fields
     * @return boolean
     */
    public static function hGet($dataModelName, $id, $fields = array())
    {

        self::_getDataModel($dataModelName, array('hash'));

        if (!empty($fields)) {
            $fields = array_intersect($fields, self::$_dataModel['cacheFields']);
        }
        $dataKey = self::_getDataKey($id);
        self::_getInstance($dataKey);


        $result = self::$_cache->hGet($dataKey, $fields);

        return $result;
    }

    /**
     * 获取key的值，字符串型，理科私有接口，一次只能取一个key
     *
     * @param type $dataModelName
     * @param type $ids
     * @return type
     */
    public static function get($dataModelName, $id)
    {

        self::_getDataModel($dataModelName, array('string', 'incr'));

        $dataKey = self::_getDataKey($id);
        self::_getInstance($dataKey);
        $result = self::$_cache->get([$dataKey]);
        $data = array();
        if (self::$_dataModel['type'] == 'string') {
            $data[$id] = json_decode($result[0], true);
        } else {
            $data[$id] = $result[0];
        }

        return $data;
    }
}
