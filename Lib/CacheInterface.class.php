<?php

namespace DevSxe\Lib;

interface CacheInterface
{

    public function del($ids);

    public function incr($id, $num);

    public function decr($id, $num);

    public function get($ids);

    public function set(array $dataset, $seconds);

    public function setNox($id, $value, $seconds);

    public function hGet($id, $fields);

    public function hSet($id, array $dataset);

    public function zSet($id, $score, $value);

    public function zDel($id, $value);

    public function zDelByRank($id, $start, $end);

    public function zDelByScore($id, $start, $end);

    public function zScor($id, $value);

    public function zRan($id, $value);

    public function zNum($id, $start, $end);

    public function zGet($id, $start, $end, $sort, $withscores);

    public function zGetByScore($id, $start, $end, $sort, $withscores, $limit);

    public function sSort($id, $limit, $sort, $alpha);

    public function listPush($id, $value, $type);

    public function listPop($id, $type);

    public function listSize($id);
}
