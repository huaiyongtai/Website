<?php

namespace DevSxe\Lib;

use DevSxe\Lib\Config;
use DevSxe\Lib\Cache;

class Storage
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

        self::$_cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', $dataModel['cacheEngine']);
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

    /**
     * 获取数据模型的keyIds
     *
     * @param type $ids
     * @param type $data
     * @return boolean
     */
    private static function _getDataModelKeyIds($ids, $data, $initMode = 0)
    {

        if (empty($ids) || !is_array($ids)) {
            throw new \Exception(self::$_dataModel['key'] . ':dataModel-ids error 3006', 3006);
        }

        $result = array();

        foreach ($ids as $_key => $_val) {
            if (!is_array($_val)) {
                throw new \Exception(self::$_dataModel['key'] . ':dataModel-ids error 3007', 3007);
            }

            $_arr = array();
            foreach ($_val as $_k => $_v) {

                $_keyExists = array_key_exists($_v, $data);

                if (!is_numeric($_v) && ($_v != 'insertId') && !$_keyExists) {
                    throw new \Exception(self::$_dataModel['key'] . ':dataModel-ids error 3008', 3008);
                }
                if (($_keyExists == true) && (strlen($data[$_v]) == 0)) {
                    if ($initMode != 0) {
                        $data[$_v] = 0;
                    } else {
                        break(2);
                    }
                }

                //如果数组不为空
                if (!empty($_arr)) {
                    //新加入的是数字或者是insertId，则在每条记录后接入
                    if (is_numeric($_v) || ($_v == 'insertId')) {
                        foreach ($_arr as $_arrK => $_arrV) {
                            $_arr[$_arrK] = $_arrV . ',' . $_v;
                        }
                    }

                    //新加入的是数组，则在每条记录*数组个数后，都接入，同时删除原有记录
                    if ($_keyExists) {
                        $_vData = explode(',', $data[$_v]);
                        foreach ($_arr as $_arrK => $_arrV) {
                            unset($_arr[$_arrK]);
                            foreach ($_vData as $_s) {
                                $_arr[] = $_arrV . ',' . $_s;
                            }
                        }
                    }
                }

                //如果数组为空
                if (empty($_arr)) {
                    //新加入的是数字或者是insertId，直接写入
                    if (is_numeric($_v) || ($_v == 'insertId')) {
                        $_arr[] = $_v;
                    }

                    //新加入的是数组，则循环写入
                    if ($_keyExists) {
                        $_vData = explode(',', $data[$_v]);
                        foreach ($_vData as $_s) {
                            $_arr[] = $_s;
                        }
                    }
                }
            }

            //数组累加合并
            if (!empty($_arr)) {
                foreach ($_arr as $_arrV) {
                    $result[] = $_arrV;
                }
            }
        }

        return $result;
    }

    /**
     * 获取数据模型的value
     *
     * @param type $value
     * @param type $data
     */
    private static function _getDataModelValue($value, $data)
    {

        if (!is_array($value)) {
            throw new \Exception(self::$_dataModel['key'] . ':dataModel-value error 3009', 3009);
        }

        $result = array();
        foreach ($value as $_key => $_val) {
            //有split为键的情况下
            if ($_key === 'split') {
                if (!isset($data[$_val])) {
                    throw new \Exception(self::$_dataModel['key'] . ':dataModel-value error 3010', 3010);
                }

                $_data = explode(',', $data[$_val]);
                foreach ($_data as $_v) {
                    $result[] = $_v;
                }
            } else {
                if ($_val != 'insertId' && !isset($data[$_val])) {
                    throw new \Exception(self::$_dataModel['key'] . ':dataModel-value error 3011', 3011);
                } elseif ($_val == 'insertId') {
                    $result[] = !empty($data['insert_id']) ? $data['insert_id'] : $data['id'];
                } else {
                    $result[] = $data[$_val];
                }
            }
        }

        return $result;
    }

    /**
     * 获取数据模型的score
     *
     * @param type $score
     * @param type $data
     */
    private static function _getDataModelScore($score, $data)
    {

        $result = '';

        switch ($score) {
            case 'value':
                $result = 'value';
                break;
            case 'insertId':
                $result = !empty($data['insert_id']) ? $data['insert_id'] : $data['id'];
                break;
            case 'defaultTime':
                $result = time();
                break;
            default:
                if (!isset($data[$score])) {
                    throw new \Exception(self::$_dataModel['key'] . ':dataModel-score error 3012', 3012);
                }

                if (is_numeric($data[$score])) {
                    $result = $data[$score];
                    break;
                }

                //判断是否是时间格式
                $result = self::_checkTime($data[$score]);
                if (empty($result)) {
                    throw new \Exception(self::$_dataModel['key'] . ':dataModel-score error 3013', 3013);
                }
                break;
        }

        return $result;
    }

    /**
     * 获取缓存自增ID，返回的是自增的第一个ID
     *
     * @return type
     */
    private static function _getCacheIncrId($num = 1)
    {

        //如果既没有数据表，也没有缓存自增键的话，默认无需自增ID
        if (empty(self::$_dataModel['dbName']) && empty(self::$_dataModel['cacheIncrKey'])) {
            return 0;
        }

        if (!empty(self::$_dataModel['cacheIncrKey'])) {
            $_incrKey = self::$_dataModel['cacheIncrKey'];
        } else {
            $_incrKey = self::$_dataModel['dbName'] . '_incr_key';
        }

        $result = self::$_cache->incr($_incrKey, $num);
        $id = $result - $num + 1;

        return $id;
    }

    /**
     * 先写数据库的情况下，更新缓存自增ID
     *
     * @param type $num
     */
    private static function _setCacheIncrid($num = 1)
    {

        if (empty(self::$_dataModel['dbName'])) {
            return false;
        }

        $_incrKey = self::$_dataModel['dbName'] . '_incr_key';
        $data[$_incrKey] = $num;
        $result = self::$_cache->set($data, 0);

        return $result;
    }

    /**
     * 数据库连接
     *
     */
    private static function _dbConn()
    {

        $_dbEngine = self::$_dataModel['dbEngine'];

        if (empty(self::$_db[$_dbEngine])) {
            self::$_db[$_dbEngine] = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', Config::get($_dbEngine));
        }

        return self::$_db[$_dbEngine];
    }

    /**
     * 从数据表获取数据
     *
     * @param type $fileds
     * @return type
     */
    private static function _getDbData($fileds = array())
    {

        $_dbName = self::$_dataModel['dbName'];

        //条件转化成SQL
        $sql = "SELECT * FROM `{$_dbName}` WHERE 1=1";

        if (!empty($fileds)) {
            foreach ($fileds as $key => $val) {
                if (empty($val['start']) && empty($val['end'])) {
                    $str = implode(',', $val);
                    $sql .= " AND `{$key}` IN ({$str})";
                }
                if (!empty($val['start'])) {
                    $sql .= " AND `{$key}` >= '{$val['start']}'";
                }
                if (!empty($val['end'])) {
                    $sql .= " AND `{$key}` <= '{$val['end']}'";
                }
            }
        }

        $sql .= " ORDER BY `id` ASC";

        //数据表读取数据并返回
        return self::_dbConn()->getPDOStatement($sql, array());
    }

    /**
     * 判断是否为时间格式
     *
     * @param type $data
     */
    private static function _checkTime($data = '')
    {

        //字符串长度，既不等于19，也不等于10的时候，则错误
        $long = strlen($data);
        if ($long != 19 && $long != 10) {
            return false;
        }

        //如果是Y-m-d H:i:s格式
        if ($long == 19) {
            $time = strtotime($data);
            $_data = date("Y-m-d H:i:s", $time);
            if ($data != $_data) {
                return false;
            }
        }

        //如果是Y-m-d格式
        if ($long == 10) {
            $time = strtotime($data);
            $_data = date("Y-m-d", $time);
            if ($data != $_data) {
                return false;
            }
        }

        return $time;
    }

    /**
     * 删除数据，支持多key
     *
     * @param type $dataModelName
     * @param type $ids
     * @return type
     */
    public static function del($dataModelName, $ids)
    {

        self::_getDataModel($dataModelName, array('string', 'incr', 'hash', 'zset', 'list'));

        foreach ($ids as $id) {
            $dataKeys[] = self::_getDataKey($id);
        }

        return self::$_cache->del($dataKeys);
    }

    /**
     * key的值自增，默认+1，只允许单键操作，int型
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $num
     * @return type
     */
    public static function incr($dataModelName, $id, $num = 1)
    {

        self::_getDataModel($dataModelName, array('incr'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->incr($dataKey, $num);
    }

    /**
     * key的值自减，默认-1，只允许单键操作，int型
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $num
     * @return type
     */
    public static function decr($dataModelName, $id, $num = 1)
    {

        self::_getDataModel($dataModelName, array('incr'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->decr($dataKey, $num);
    }

    /**
     * 支持多次操作，并且保证为原子操作
     *
     * $dataset 数据格式定义
     *  array(
     *      'key' => 'value'
     *      'key' => 'value'
     *      .....
     *  );
     * $dataset中的key: 为一个字符串，如果key有多id组成，之间用逗号分割
     *      例如:下面为用户购买的未学过的课程集合的key的定义
     *      user_course_$1_$2
     *          $1 课程id
     *          $2 状态 1:已学 2:未学
     *      'key' => '31,1',
     *
     * $dataset => array(
     *      '31, 1' => 'value',
     *      '31, 1' => 'value',
     *      .....
     *  )
     *
     * @param array $dataset
     */
    public static function set($dataModelName, $dataset, $seconds = 0)
    {

        self::_getDataModel($dataModelName, array('string', 'incr'));

        /**
         *
         * $set 内部变量
         * 定义多key插入的数据集
         *
         * 数据格式
         *  $set = array(
         *      'course_2_1' => 'xxxxxxxx',
         *      'course_2_2' => 'aaaaaaaa',
         *      .....
         *  );
         */
        $data = array();
        foreach ($dataset as $key => $val) {
            $dataKey = self::_getDataKey($key);
            if (self::$_dataModel['type'] == 'string') {
                $data[$dataKey] = json_encode($val);
            } else {
                $data[$dataKey] = $val;
            }
        }

        self::$_cache->set($data, $seconds);

        return $data;
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

    /**
     * string型，不存在，写入并返回true，否则不写入并返回false
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $value
     */
    public static function setnx($dataModelName, $id, $value, $seconds = 0)
    {

        self::_getDataModel($dataModelName, array('string'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->setNox($dataKey, $value, $seconds);
    }

    /**
     * 将hash类型的多条数据写入缓存和数据库
     *
     * @param type $dataModelName
     * @param type $dataset
     */
    public static function hAdd($dataModelName, $dataset)
    {
        self::_getDataModel($dataModelName, array('hash'));

        $num = count($dataset);

        //参数值
        foreach ($dataset as $key => $val) {
            $dataset[$key]['paramsIds'] = !empty($val['paramsIds']) ? $val['paramsIds'] : 'insertId';
        }

        if (!empty(self::$_dataModel['isSave'])) {
            //需要添加的数据表数据
            $dbData = array();
            foreach ($dataset as $key => $val) {
                foreach (self::$_dataModel['dbFields'] as $field) {
                    if (isset($val[$field]) && $field != 'id') {
                        $dbData[$key][$field] = $val[$field];
                    }
                }
            }
        }

        //需要先插入数据表的
        if (!empty(self::$_dataModel['isSave']) && (self::$_dataModel['saveMode'] == 1)) {

            //写入数据库，返回第一条数据的ID
            self::_dbConn()->cs(self::$_dataModel['dbName'], $dbData);
            $id = self::_dbConn()->lastInsertId();

            //同时把自增ID写入缓存
            $lastId = $id - 1 + $num;
            self::_setCacheIncrid($lastId);
        }

        //缓存的自增的第一个ID
        if ((!empty(self::$_dataModel['isSave']) && self::$_dataModel['saveMode'] == 2) || empty(self::$_dataModel['isSave'])) {
            $id = self::_getCacheIncrId($num);
        }

        //缓存数据
        $data = array();
        $_id = $id;
        foreach ($dataset as $key => $val) {

            //自增ID
            $key = str_replace('insertId', $_id, $val['paramsIds']);
            $dataKey = self::_getDataKey($key);

            foreach (self::$_dataModel['cacheFields'] as $field) {
                if (isset($val[$field])) {
                    $data[$key][$field] = $val[$field];
                }
            }

            //处理ID
            $data[$key]['id'] = $_id;
            $_id ++;

            //写入缓存
            self::$_cache->hSet($dataKey, $data[$key]);
        }

        //需要后插入数据表的
        if (!empty(self::$_dataModel['isSave']) && (self::$_dataModel['saveMode'] == 2)) {
            foreach ($dbData as $key => $val) {
                $dbData[$key]['id'] = $id;
                $id ++;
            }

            //写入数据库
            self::_dbConn()->cs(self::$_dataModel['dbName'], $dbData);
        }

        return $data;
    }

    /**
     * 将hash类型的多条数据更新到缓存和数据库
     *
     * @param type $dataModelName
     * @param type $dataset
     */
    public static function hSet($dataModelName, $dataset, $dbField = 'id')
    {

        self::_getDataModel($dataModelName, array('hash'));

        foreach ($dataset as $key => $val) {

            if (!empty(self::$_dataModel['isSave'])) {
                //数据表自增ID
                if (!empty($val['id'])) {
                    $id = $val['id'];
                } else {
                    $_key = explode(',', $key);
                    $id = $_key[count($_key) - 1];
                }

                //需要修改的数据表数据
                $dbData = array();
                foreach (self::$_dataModel['dbFields'] as $field) {
                    if (isset($val[$field]) && $field != $dbField) {
                        $dbData[$field] = $val[$field];
                    }
                }

                //更新数据表
                self::_dbConn()->u(self::$_dataModel['dbName'], $dbData, $id, $dbField);
            }

            //需要更新的缓存数据
            $cacheData = array();
            foreach (self::$_dataModel['cacheFields'] as $field) {
                if (isset($val[$field])) {
                    $cacheData[$field] = $val[$field];
                }
            }

            $dataKey = self::_getDataKey($key);
            //更新缓存
            self::$_cache->hSet($dataKey, $cacheData);
        }

        return $dataset;
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
        $result = self::$_cache->hGet($dataKey, $fields);

        return $result;
    }

    /**
     * hash类型数据初始化，一条数据，先删除原缓存再写入新缓存数据
     *
     * @param type $dataset
     * @param type $cacheKeys
     */
    private static function _hInit($dataset, $cacheKeys)
    {

        //缓存的key处理
        $keys = array();
        foreach ($cacheKeys as $cacheKey) {
            $cacheKey = ($cacheKey == 'insertId') ? 'id' : $cacheKey;
            $keys[] = $dataset[$cacheKey];
        }

        $dataKey = self::_getDataKey(implode(',', $keys));

        $data = array();
        if (!empty($dataset['id'])) {
            $data['id'] = $dataset['id'];
        }
        foreach (self::$_dataModel['cacheFields'] as $field) {
            if (isset($dataset[$field])) {
                $data[$field] = $dataset[$field];
            }
        }

        //先删除缓存
        self::$_cache->del($dataKey);
        //写入新缓存
        self::$_cache->hSet($dataKey, $data);

        return $data;
    }

    /**
     *  hash类型数据初始化，从数据表更新至缓存
     *
     * @param type $dataModelName
     * @param type $start
     * @param type $end
     */
    public static function hInit($dataModelName, $fileds = array(), $cacheKeys = array('id'))
    {

        self::_getDataModel($dataModelName, array('hash'));

        if (empty(self::$_dataModel['isSave']) || empty(self::$_dataModel['dbName'])) {
            return false;
        }

        //数据表读取数据
        $datas = self::_getDbData($fileds);

        //循环初始化缓存数据
        while ($val = $datas->fetch(\PDO::FETCH_ASSOC)) {
            self::_hInit($val, $cacheKeys);
            $data[] = $val;
        }

        return $data;
    }

    /**
     * redis hincrby
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $field
     * @param type $value
     */
    public static function hIncrBy($dataModelName, $id, $field, $value)
    {

        self::_getDataModel($dataModelName, array('hash'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->hIncrBy($dataKey, $field, $value);
    }

    /**
     * 有序集合key的自增
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $score
     * @param type $value
     */
    public static function zIncrBy($dataModelName, $id, $score, $value)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        if (!is_numeric($score)) {
            return false;
        }

        return self::$_cache->zIncrBy($dataKey, $score, $value);
    }

    /**
     * 有序集合，单数据写入关系
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $score
     * @param type $value
     */
    public static function zAdd($dataModelName, $id, $score, $value)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        if (!is_numeric($score)) {
            return false;
        }

        return self::$_cache->zSet($dataKey, $score, $value);
    }

    /**
     * 有序集合，单数据移除关系
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $value
     * @return boolean
     */
    public static function zDel($dataModelName, $id, $value)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zDel($dataKey, $value);
    }

    /**
     * 有序集合，单数据移除关系 指定rank区间
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $start
     * @param type $end
     * @return type
     */
    public static function zDelByRank($dataModelName, $id, $start, $end)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zDelByRank($dataKey, $start, $end);
    }

    /**
     * 有序集合，单数据移除关系 根据score的区间删除 删除单个 scorestart ～ scoreend即可
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $scorestart
     * @param type $scoreend
     * @return boolean
     */
    public static function zDelByScore($dataModelName, $id, $scorestart, $scoreend)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zDelByScore($dataKey, $scorestart, $scoreend);
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

        return self::$_cache->zScore($dataKey, $value);
    }

    /**
     * 有序集合，单key下，某元素的排序
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $value
     * @return type
     */
    public static function zRank($dataModelName, $id, $value)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zRank($dataKey, $value);
    }

    /**
     * 有序集合，单KEY下，指定区间内的，返回成员值
     * sort排序：1从小到大，2从大到小
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $start
     * @param type $end
     * @param type $sort
     * @param type $withscores
     * @return boolean
     */
    public static function zGet($dataModelName, $id, $start = 0, $end = -1, $sort = 1, $withscores = false)
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zGet($dataKey, $start, $end, $sort, $withscores);
    }

    /**
     * 有序结合，单KEY下，score在指定区间内的，返回成员值
     * sort排序：1从小到大，2从大到小
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $start
     * @param type $end
     * @param type $sort
     * @param type $withscores
     * @param type $limit
     * @return boolean
     */
    public static function zGetByScore($dataModelName, $id, $start = 0, $end = 1, $sort = 1, $withscores = false,
        $limit = array())
    {

        self::_getDataModel($dataModelName, array('zset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->zGetByScore($dataKey, $start, $end, $sort, $withscores, $limit);
    }

    /**
     * 集合查询
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $limit
     * @param type $sort
     * @param type $alpha
     */
    public static function sSort($dataModelName, $id, $limit = array(), $sort = 'ASC', $alpha = false)
    {

        self::_getDataModel($dataModelName, array('sset'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->sSort($dataKey, $limit, $sort, $alpha);
    }

    /**
     * LIST push操作
     *
     * @param type $id
     * @param type $value
     * @param type $type
     * @return type
     */
    public static function lPush($dataModelName, $id = '', $value, $type = 1)
    {

        self::_getDataModel($dataModelName, array('list'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->listPush($dataKey, $value, $type);
    }

    /**
     * LIST pop操作
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $type
     * @return type
     */
    public static function lPop($dataModelName, $id = '', $type = 1)
    {

        self::_getDataModel($dataModelName, array('list'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->listPop($dataKey, $type);
    }

    /**
     * LIST size操作
     *
     * @param type $dataModelName
     * @param type $id
     * @return type
     */
    public static function lSize($dataModelName, $id = '')
    {

        self::_getDataModel($dataModelName, array('list'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->listSize($dataKey);
    }

    /**
     * 综合型数据模型，添加数据，根据配置调用其他dataModel
     *
     * @param type $dataModelName
     * @param type $dataset
     * @return boolean
     */
    public static function complexAdd($dataModelName, $dataset)
    {

        self::_getDataModel($dataModelName, array('complex'));

        if (empty(self::$_dataModel['relationModel'])) {
            return false;
        }

        //关系模型
        $relationModels = self::$_dataModel['relationModel'];

        foreach ($relationModels as $relationModel) {

            //关系模型的keyName
            self::_getDataModel($relationModel['keyName']);

            //关系模型的key参数
            $ids = self::_getDataModelKeyIds($relationModel['ids'], $dataset);

            //处理具体模型，如果有哈希类型必须第一个处理
            if (self::$_dataModel['type'] == 'hash') {
                //如果是hash类型，只处理第一个
                $dataset['paramsIds'] = $ids[0];
                $_data = self::hAdd($relationModel['keyName'], array($dataset));

                //自增ID处理
                $_data = array_values($_data);
                $dataset['insert_id'] = $_data[0]['id'];
            }

            //有序集合类型的处理
            if (self::$_dataModel['type'] == 'zset') {
                //value处理
                $value = self::_getDataModelValue($relationModel['value'], $dataset);

                //score处理
                $score = self::_getDataModelScore($relationModel['score'], $dataset);

                //循环写入关系
                foreach ($ids as $id) {
                    foreach ($value as $val) {
                        if ($score === 'value') {
                            $score = $val;
                        }

                        self::zAdd($relationModel['keyName'], $id, $score, $val);
                    }
                }
            }

            //自增类型的处理
            if (self::$_dataModel['type'] == 'incr') {
                //循环自增数值
                foreach ($ids as $id) {
                    if (!empty($dataset['insert_id'])) {
                        $id = str_replace('insertId', $dataset['insert_id'], $id);
                    }

                    $incrNum = 1;
                    if (is_int($relationModel['value'])) {
                        $incrNum = $relationModel['value'];
                    } elseif (isset($dataset[$relationModel['value']])) {
                        $incrNum = $dataset[$relationModel['value']];
                    }

                    self::incr($relationModel['keyName'], $id, $incrNum);
                }
            }

            //字符串类型的处理
            if (self::$_dataModel['type'] == 'string') {
                //value处理
                $value = self::_getDataModelValue($relationModel['value'], $dataset);

                //循环写入字符串
                foreach ($ids as $id) {
                    if (!empty($dataset['insert_id'])) {
                        $id = str_replace('insertId', $dataset['insert_id'], $id);
                    }

                    if (!empty($id)) {
                        self::set($relationModel['keyName'], array($id => $value));
                    }
                }
            }
        }

        if (!empty($dataset['insert_id'])) {
            return $dataset['insert_id'];
        }

        return true;
    }

    /**
     * 综合型数据模型，移除数据，根据配置调用其他dataModel
     *
     * @param type $dataModelName
     * @param type $dataset
     * @return boolean
     */
    public static function complexDel($dataModelName, $dataset)
    {

        self::_getDataModel($dataModelName, array('complex'));

        if (empty(self::$_dataModel['relationModel'])) {
            return false;
        }

        //关系模型
        $relationModels = self::$_dataModel['relationModel'];

        foreach ($relationModels as $relationModel) {

            //关系模型的keyName
            self::_getDataModel($relationModel['keyName']);

            //关系模型的key参数
            $ids = self::_getDataModelKeyIds($relationModel['ids'], $dataset);

            //哈希类型的处理
            if (self::$_dataModel['type'] == 'hash') {
                if (!empty($dataset['id'])) {
                    $_id = str_replace('insertId', $dataset['id'], $ids[0]);
                } else {
                    $_id = $ids[0];
                }

                self::hSet($relationModel['keyName'], array($_id => $dataset));
            }

            //有序集合类型的处理
            if (self::$_dataModel['type'] == 'zset') {
                //value处理
                $value = self::_getDataModelValue($relationModel['value'], $dataset);

                //循环移除关系
                foreach ($ids as $id) {
                    foreach ($value as $val) {
                        self::zDel($relationModel['keyName'], $id, $val);
                    }
                }
            }

            //自增类型的处理
            if (self::$_dataModel['type'] == 'incr') {
                //循环自减数值
                foreach ($ids as $id) {
                    if (!empty($dataset['id'])) {
                        $id = str_replace('insertId', $dataset['id'], $id);
                    }

                    $incrNum = 1;
                    if (is_int($relationModel['value'])) {
                        $incrNum = $relationModel['value'];
                    } elseif (isset($dataset[$relationModel['value']])) {
                        $incrNum = $dataset[$relationModel['value']];
                    }

                    self::decr($relationModel['keyName'], $id, $incrNum);
                }
            }

            //字符串类型的处理
            if (self::$_dataModel['type'] == 'string') {
                //value处理
                $value = self::_getDataModelValue($relationModel['value'], $dataset);

                //循环写入字符串
                foreach ($ids as $id) {
                    if (!empty($dataset['id'])) {
                        $id = str_replace('insertId', $dataset['id'], $id);
                    }

                    if (!empty($id)) {
                        self::set($relationModel['keyName'], array($id => $value));
                    }
                }
            }
        }

        if (!empty($dataset['id'])) {
            return $dataset['id'];
        }

        return true;
    }

    /**
     * 综合型数据模型，初始化数据，根据配置调用其他dataModel
     *
     * @param type $dataModelName
     * @param type $fileds
     * @param type $isDel 是否删除有序集合数据，默认1删除
     */
    public static function complexInit($dataModelName, $fileds = array(), $isDel = 1, $initMode = 0)
    {

        self::_getDataModel($dataModelName, array('complex'));

        if (empty(self::$_dataModel['dbName'])) {
            return false;
        }

        if (empty(self::$_dataModel['relationModel'])) {
            return false;
        }

        //关系模型
        $relationModels = self::$_dataModel['relationModel'];

        //数据表读取数据
        $datas = self::_getDbData($fileds);

        //循环初始化
        while ($dataset = $datas->fetch(\PDO::FETCH_ASSOC)) {

            //循环处理关系模型
            foreach ($relationModels as $relationModel) {

                //关系模型的keyName
                self::_getDataModel($relationModel['keyName']);

                //关系模型的key参数
                $ids = self::_getDataModelKeyIds($relationModel['ids'], $dataset, $initMode);

                //处理具体模型，如果有哈希类型必须第一个处理
                if (self::$_dataModel['type'] == 'hash') {
                    self::_hInit($dataset, $ids);
                }

                //有序集合类型的处理
                if (self::$_dataModel['type'] == 'zset') {
                    //value处理
                    $value = self::_getDataModelValue($relationModel['value'], $dataset);

                    //score处理
                    $score = self::_getDataModelScore($relationModel['score'], $dataset);

                    //循环写入关系
                    foreach ($ids as $id) {
                        foreach ($value as $val) {
                            //是否删除过，未删除则先删除原有关系(isDel=1时)
                            if (!empty($isDel) && empty($_isDel[$relationModel['keyName'] . $id])) {
                                self::del($relationModel['keyName'], array($id));
                            }
                            $_isDel[$relationModel['keyName'] . $id] = 1;

                            if ($score == 'value') {
                                $score = $val;
                            }
                            self::zAdd($relationModel['keyName'], $id, $score, $val);
                        }
                    }
                }

                //自增类型的处理
                if (self::$_dataModel['type'] == 'incr') {
                    //循环自增数值
                    foreach ($ids as $id) {
                        if (!empty($dataset['insert_id'])) {
                            $id = str_replace('insertId', $dataset['insert_id'], $id);
                        }

                        $incrNum = 1;
                        if (is_int($relationModel['value'])) {
                            $incrNum = $relationModel['value'];
                        } elseif (isset($dataset[$relationModel['value']])) {
                            $incrNum = $dataset[$relationModel['value']];
                        }

                        if (empty($incrArr[$relationModel['keyName']][$id])) {
                            $incrArr[$relationModel['keyName']][$id] = $incrNum;
                        } else {
                            $incrArr[$relationModel['keyName']][$id] += $incrNum;
                        }
                    }
                }

                //字符串类型的处理
                if (self::$_dataModel['type'] == 'string') {
                    //value处理
                    $value = self::_getDataModelValue($relationModel['value'], $dataset);

                    //循环写入字符串
                    foreach ($ids as $id) {
                        if (!empty($dataset['id'])) {
                            $id = str_replace('insertId', $dataset['id'], $id);
                        }

                        if (!empty($id)) {
                            $stringArr[$relationModel['keyName']][$id] = $value;
                        }
                    }
                }
            }
        }

        //如果有自增值
        if (!empty($incrArr)) {
            foreach ($incrArr as $key => $val) {
                self::set($key, $val);
            }
        }

        //如果有字符串
        if (!empty($stringArr)) {
            foreach ($stringArr as $key => $val) {
                self::set($key, $val);
            }
        }

        return true;
    }

    /**
     * Redis hset
     *
     * @param type $dataModelName
     * @param type $id
     * @param type $arr
     */
    public static function redHset($dataModelName, $id, $arr)
    {
        self::_getDataModel($dataModelName, array('hash'));

        $dataKey = self::_getDataKey($id);

        return self::$_cache->hset($dataKey, $arr);
    }

}
