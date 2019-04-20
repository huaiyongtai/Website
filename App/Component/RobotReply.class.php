<?php

/*
 * 微群管家自动回复
 *   type 回复内容查找范围
 *      1. 全局回复
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Storage;

class RobotReply
{

    private $cacheFields = ['id', 'question', 'answer', 'type', 'status', 'is_del'];

    /**
     * 缓存引擎
     */
    const CACHE_KEY = 'wx_robot_resp_%s';
    const CACHE_ENGINE = '\DevSxe\Application\Config\Storage\Default\redisdb';

    /**
     * 默认提问内容
     */
    const DEFAULT_QUESTION = '默认回复';

    /**
     * 默认回复内容
     */
    const DEFAULT_REPLY = '抱歉！暂时无法解决该问题，您可以咨询开发测试客服, 电话： 400-800-2211';

    /**
     * 获取特地区间的回复信息列表
     * @param int $offset   查询偏移量
     * @param int $length   查询数据量
     * @param int $type     类型（默认全局回复）
     * @return array 关键词列表信息数组
     */
    public function listInfo($offset, $length, $type = 1, $where = '1=1')
    {
        if ($offset * $length < 0 || $length == 0) {
            return array();
        }

        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $total = $robotRM->total($type, $where);
        if ($offset > $total) {
            return array();
        }

        $listInfo = $robotRM->listInfo($offset, $length, $type, $where);
        return array(
            'listInfo' => $listInfo,
            'total' => $total,
        );
    }

    /**
     * 是否存在指定回复词
     */
    public function getByQuestion($question, $type = 1)
    {
        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);
        $matchInfo = $cache->hGet(sprintf(self::CACHE_KEY, $type), [$question]);
        if (empty($matchInfo[$question])) {
            return array();
        }
        return json_decode($matchInfo[$question], true);
    }

    /**
     * 响应处理问题
     */
    public function respForQuestion($question, $type = 1)
    {
        if (empty($question)) {
            return ['stat' => 1, 'data' => self::DEFAULT_REPLY];
        }
        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);

        //1. 查缓存中是否有匹配的回复
        $matchKeys = [$question, self::DEFAULT_QUESTION];
        $matchRespInfo = $cache->hGet(sprintf(self::CACHE_KEY, $type), $matchKeys);
        foreach ($matchKeys as $val) {
            $respInfo = json_decode($matchRespInfo[$val], true);
            if (empty($respInfo)) {
                continue;
            }
            if ($respInfo['status'] == 1 && $respInfo['is_del'] != 1) {
                return ['stat' => 1, 'data' => $respInfo['answer']];
            }
        }

        //2. 回复默认答案
        return ['stat' => 1, 'data' => self::DEFAULT_REPLY];
    }

    /**
     * 添加回复词
     * @param array $params 添加回复词信息
     */
    public function add($params)
    {
        // 0. 检测是否存在
        if (empty($params['question']) || empty($params['type'])) {
            return ['stat' => 0, 'data' => '添加信息不完整'];
        }

        $params['status'] = 1;
        $params['is_del'] = 0;
        $params['create_time'] = date('Y-m-d H:i:s');

        // 1. 先入库
        $respInfo = $this->getByQuestion($params['question'], $params['type']);
        if (!empty($respInfo)) {
            if ($respInfo['is_del'] == 1) {
                return $dbResult = $this->editInfo($respInfo['id'], $params);
            } else {
                return ['stat' => 0, 'data' => '已存该关键词'];
            }
        }

        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $dbResult = $robotRM->add($params);
        if ($dbResult == false) {
            ['stat' => 0, 'data' => '回复信息写入失败'];
        }
        // 2. 入缓存
        $params['id'] = 6;
        $this->_respCacheSave($params);

        return ['stat' => 1, 'data' => 'success'];
    }

    /**
     * 更新自动回复词状态
     * @param type $question
     * @param type $type
     * @param type $toStatus
     * @return boolean
     */
    public function editInfo($id, $params)
    {
        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $respInfo = $robotRM->getById($id);
        if (empty($respInfo)) {
            return ['stat' => 0, 'data' => '待编辑关键词不存在'];
        }

        // 0. 待更新数据
        foreach ($params as $key => $value) {
            if (!isset($respInfo[$key])) {
                continue;
            }
            $respInfo[$key] = $value;
        }

        // 1. 更新数据
        $result = $robotRM->update($id, $respInfo);
        if ($result == false) {
            return ['stat' => 0, 'data' => '数据更新失败'];
        }

        $this->_respCacheSave($respInfo);
        return ['stat' => 1, 'data' => 'success'];
    }

    /**
     * 更新自动回复词状态
     * @param type $id
     * @param type $toStatus
     * @return bool
     */
    public function changeStatus($id, $toStatus)
    {
        $result = $this->editInfo($id, ['status' => $toStatus]);
        return $result;
    }

    /**
     * 删除指定Id词
     * @param type $id
     */
    public function del($id)
    {
        $result = $this->editInfo($id, ['is_del' => 1]);
        return $result;
    }

    /**
     * 回复信息写入到缓存中
     * @param type $params
     * @return boolean
     */
    private function _respCacheSave($params)
    {
        if (empty($params['type']) || empty($params['question'])) {
            return false;
        }

        $saveFields = array();
        foreach ($this->cacheFields as $val) {
            if (!isset($params[$val])) {
                continue;
            }
            $saveFields[$val] = $params[$val];
        }

        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);
        $cache->hSet(sprintf(self::CACHE_KEY, $params['type']), [$params['question'] => json_encode($saveFields)]);
    }

    /**
     * 获取关键词列表
     * 
     * @param string $keyword 关键词
     * @param int $curpage  当前页
     * @param int $perpage  条数
     * @return array
     */
    public function lists($keyword, $curpage, $perpage)
    {
        $fields = '`id`, `keyword`, `content`, `num`, `create_name`, `create_time`';

        $where = 'WHERE `is_del`=0 ';
        if ($keyword != '') {
            $where .= " AND `keyword` = '{$keyword}'";
        }

        $order = '';
        $limit = '';
        if (isset($curpage) && isset($perpage)) {
            $order = "ORDER BY `id` DESC";
            $limit = "LIMIT " . ($curpage - 1) * $perpage . "," . $perpage;
        }

        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $result = $robotRM->searchKeyword($fields, $where, $order, $limit);
        return $result;
    }

    /**
     * 添加关键词
     */
    public function addKeyword($params)
    {
        $params['create_time'] = date('Y-m-d H:i:s');
        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $result = $robotRM->addKeyword($params);
        if ($result === false) {
            return '添加失败';
        }
        //加入缓存
        $key = $params['keyword'] . ',' . $params['num'];
        Storage::set('\DevSxe\Application\Config\DataModel\Wx\Robot\keyword', array($key => $params['content']));
        return array('stat' => 1, 'data' => '添加成功');
    }

    /**
     * 获取关键词
     * 
     * @param string $keyword 关键词
     * @param int    $num 建群天数
     */
    public function getKeyword($keyword, $num)
    {
        $key = $keyword . ',' . $num;
        $result = Storage::get('\DevSxe\Application\Config\DataModel\Wx\Robot\keyword', array($key));
        if (empty($result[$key])) {
            return array();
        }
        return explode('[devsxe]' ,$result[$key]);
    }

    /**
     * 删除关键词
     */
    public function delKeyword($id)
    {
        $robotRM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\RobotReply');
        $keyInfo = $robotRM->getKeywordInfo($id);
        if (empty($keyInfo)) {
            return '获取关键词信息失败';
        }
        $result = $robotRM->updKeyword($id, array('is_del' => 1));
        if ($result == false) {
            return '数据更新失败';
        }

        $key = $keyInfo['keyword'] . ',' . $keyInfo['num'];
        Storage::del('\DevSxe\Application\Config\DataModel\Wx\Robot\keyword', array($key));
        return array('stat' => 1, 'data' => '删除成功');
    }

    /**
     * 添加群创建信息
     */
    public function addGroupInfo($params)
    {
        Storage::set('\DevSxe\Application\Config\DataModel\Wx\Robot\groupCreateTime',
            array($params['groupid'] => strtotime(date('Y-m-d'))));
        return array('stat' => 1, 'data' => '添加成功');
    }

    /**
     * 获取群创建信息
     */
    public function getGroupInfo($params)
    {
        $result = Storage::get('\DevSxe\Application\Config\DataModel\Wx\Robot\groupCreateTime', array($params['groupid']));
        if (empty($result[$params['groupid']])) {
            return array();
        }
        return $result[$params['groupid']];
    }

}
