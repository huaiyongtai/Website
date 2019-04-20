<?php

namespace DevSxe\Lib;

include_once DEVSXE_ROOT_DIR . '/Lib/CacheInterface.class.php';

class Redisdb implements \DevSxe\Lib\CacheInterface
{

    private $_conn;

    private $_config;

    public function __construct($dbConfig)
    {
        $this->_config = $dbConfig;
        $this->_conn = new \redis();
        try {
            $this->_conn->connect($dbConfig['host'], $dbConfig['port']);

            if (isset($dbConfig['auth'])) {
                $this->_conn->auth($dbConfig['auth']);
            }
            if (isset($dbConfig['db_index'])) {
                $this->_conn->select($dbConfig['db_index']);
            }
        } catch (RedisException $e) {
            $msg = $e->getMessage();
            $host = $this->_config['host'];
            $port = $this->_config['port'];
            throw new \RedisException($msg . " [host] => $host [port] => $port");

        }
    }

    /**
     * 记录Redis执行明细
     *
     * @param float $stime 开始时间
     * @param float $etime 结束时间
     */
    private function _redisLog($stime, $etime, $func, $args, $result)
    {

        if (Core::$conf['pdo'] == 1) {
            $time = $etime - $stime;
            $time = sprintf("%.3f", $time * 1000);
            Core::$time['redis']['details'][] = array(
                'func' => $func,
                'args' => $args,
                'time' => $time,
                'result' => $result,
            );
            Core::$time['redis']['time'] += $time;
            Core::$time['redis']['cnt'] ++;
        }
    }

    public function __call($func, $args)
    {
        try {
            //echo $func."\n";
            //这里可以记录语句、返回值、时间等
            $stime = getMicrotime();
            /* $args = $args[0]; */
            //print_r($args);

            $result = (call_user_func_array(array($this->_conn, $func), $args));

            $etime = getMicrotime();
            $this->_redisLog($stime, $etime, $func, $args, $result);
            return $result;
        } catch (\RedisException $e) {
            $msg = $e->getMessage();
            $host = $this->_config['host'];
            $port = $this->_config['port'];
            throw new \RedisException($msg . " [host] => $host [port] => $port");
            //var_dump($msg, $host);exit;
        } 
    }

    public function del($ids)
    {
        return $this->delete($ids);
    }

    public function incr($id, $num)
    {
        if (is_float($num)) {
            return $this->incrByFloat($id, $num);
        } else {
            return $this->incrBy($id, $num);
        }
    }

    public function decr($id, $num)
    {
        return $this->decrBy($id, $num);
    }

    public function get($ids)
    {
        return $this->mGet($ids);
    }

    public function set(array $dataset, $seconds)
    {
        if (empty($seconds)) {
            return $this->mSet($dataset);
        }

        $data = array();
        foreach ($dataset as $key => $value) {
            $data[] = $this->setex($key, $seconds, $value);
            return $data;
        }
    }

    public function setNox($id, $value, $seconds)
    {
        if ($seconds == 0) {
            $status = $this->setnx($id, $value);
        } else {
            $status = $this->setnx($id, $value);
            $this->setTimeout($id, $seconds);
        }

        return $status;
    }

    public function hGet($id, $fields)
    {
        if (empty($fields)) {
            return $this->hGetAll($id);
        } else {
            return $this->hMGet($id, $fields);
        }
    }

    public function hSet($id, array $dataset)
    {
        return $this->hMset($id, $dataset);
    }

    public function zSet($id, $score, $value)
    {
        return $this->zAdd($id, $score, $value);
    }

    public function zDel($id, $value)
    {
        return $this->zRem($id, $value);
    }

    public function zDelByRank($id, $start, $end)
    {
        return $this->zRemRangeByRank($id, $start, $end);
    }

    public function zDelByScore($id, $start, $end)
    {
        return $this->zRemRangeByScore($id, $start, $end);
    }

    public function zScor($id, $value)
    {
        return $this->zScore($id, $value);
    }

    public function zRan($id, $value)
    {
        return $this->zRank($id, $value);
    }

    public function zNum($id, $start, $end)
    {

        if (empty($start) && empty($end)) {
            return $this->zCard($id);
        } else {
            return $this->zCount($id, $start, $end);
        }
    }

    public function zGet($id, $start, $end, $sort, $withscores)
    {
        if ($sort == 1) {
            return $this->zRange($id, $start, $end, $withscores);
        } else {
            return $this->zRevRange($id, $start, $end, $withscores);
        }
    }

    public function zGetByScore($id, $start, $end, $sort, $withscores, $limit)
    {

        $arr['withscores'] = $withscores;
        $arr['limit'] = $limit;

        if ($sort == 1) {
            return $this->zRangeByScore($id, $start, $end, $arr);
        } else {
            return $this->zRevRangeByScore($id, $start, $end, $arr);
        }
    }

    public function sSort($id, $limit, $sort, $alpha)
    {

        return $this->sort($id, array('limit' => $limit, 'sort' => $sort, 'alpha' => $alpha));
    }

    public function listPush($id, $value, $type)
    {

        if ($type == 1) {
            return $this->lPush($id, $value);
        } else {
            return $this->rPush($id, $value);
        }
    }

    public function listPop($id, $type)
    {
        if ($type == 1) {
            return $this->lPop($id);
        } else {
            return $this->rPop($id);
        }
    }

    public function listSize($id)
    {
        return $this->lSize($id);
    }

}
