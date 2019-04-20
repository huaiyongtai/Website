<?php

namespace DevSxe\Lib;

use DevSxe\Lib\Redisdb;

class Cache
{

    /**
     * 当前缓存实例
     *
     * @var object
     */
    private $_cacheInstance = 'Redisdb';

    /**
     * 构造一个cache类，并根据cache类别和配置，返回不同的对象
     *
     * @param string $cacheEngine 选择的cache引擎类别
     */
    public function __construct($cacheEngine)
    {
        $cacheConfig = Config::get($cacheEngine);

        $this->_cacheInstance = \DevSxe\Lib\G($cacheConfig['engine'], $cacheConfig);
    }

    public function del($ids)
    {
        return $this->_cacheInstance->del($ids);
    }

    public function incr($id, $num)
    {
        return $this->_cacheInstance->incr($id, $num);
    }

    public function decr($id, $num)
    {
        return $this->_cacheInstance->decr($id, $num);
    }

    public function get($ids)
    {
        return $this->_cacheInstance->get($ids);
    }

    public function set(array $dataset, $seconds)
    {
        return $this->_cacheInstance->set($dataset, $seconds);
    }

    public function setNox($id, $value, $seconds)
    {
        return $this->_cacheInstance->setNox($id, $value, $seconds);
    }

    public function hGet($id, $fields)
    {
        return $this->_cacheInstance->hGet($id, $fields);
    }

    public function hSet($id, array $dataset)
    {
        return $this->_cacheInstance->hSet($id, $dataset);
    }

    public function hIncrBy($id, $field, $value)
    {
        return $this->_cacheInstance->hIncrBy($id, $field, $value);
    }

    public function zSet($id, $score, $value)
    {
        return $this->_cacheInstance->zSet($id, $score, $value);
    }

    public function zDel($id, $value)
    {
        return $this->_cacheInstance->zDel($id, $value);
    }

    public function zDelByRank($id, $start, $end)
    {
        return $this->_cacheInstance->zDelByRank($id, $start, $end);
    }

    public function zDelByScore($id, $start, $end)
    {
        return $this->_cacheInstance->zDelByScore($id, $start, $end);
    }

    public function zScore($id, $value)
    {
        return $this->_cacheInstance->zScor($id, $value);
    }

    public function zRank($id, $value)
    {
        return $this->_cacheInstance->zRan($id, $value);
    }

    public function zCount($id, $start, $end)
    {
        return $this->_cacheInstance->zNum($id, $start, $end);
    }

    public function zGet($id, $start, $end, $sort, $withscores)
    {
        return $this->_cacheInstance->zGet($id, $start, $end, $sort, $withscores);
    }

    public function zGetByScore($id, $start, $end, $sort, $withscores, $limit)
    {
        return $this->_cacheInstance->zGetByScore($id, $start, $end, $sort, $withscores, $limit);
    }

    public function zIncrBy($id, $score, $value)
    {
        return $this->_cacheInstance->zIncrBy($id, $score, $value);
    }

    public function sSort($id, $limit, $sort, $alpha)
    {
        return $this->_cacheInstance->sSort($id, $limit, $sort, $alpha);
    }

    public function listPush($id, $value, $type)
    {
        return $this->_cacheInstance->listPush($id, $value, $type);
    }

    public function listPop($id, $type)
    {
        return $this->_cacheInstance->listPop($id, $type);
    }

    public function listSize($id)
    {
        return $this->_cacheInstance->listSize($id);
    }

}
