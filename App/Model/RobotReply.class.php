<?php

/*
 * 机器人回复
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class RobotReply extends Model
{

    protected $db = null;

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 关键词列表详细信息
     * @param int $offset
     * @param int $length
     * @param int $type
     * @return array
     */
    public function listInfo($offset, $length, $type, $where = '1=1')
    {
        $sql = <<<DEVSXE
            SELECT
                `id`,
                `question`,
                `answer`,
                `type`,
                `status`,
                `create_name`,
                `create_time`
            FROM `wx_robot_reply`
            WHERE `type` = '{$type}'
            AND `is_del` = '0'
            {$where}
            ORDER BY id DESC LIMIT {$offset}, {$length}
DEVSXE;
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 根据id获取回复信息
     * @param type $id
     * @return array 自动回复信息
     */
    public function getById($id)
    {
        $sql = <<<DEVSXE
            SELECT
                `id`,
                `question`,
                `answer`,
                `type`,
                `status`,
                `is_del`,
                `create_id`,
                `create_name`,
                `create_time`
            FROM `wx_robot_reply`
            WHERE `id` = '{$id}'
            LIMIT 1
DEVSXE;
        $result = $this->db->g($sql, array());
        return $result[0];
    }

    /**
     * 获取指定类型的关键词总数
     * @param type $type
     * @return int 总数
     */
    public function total($type, $where = '1=1')
    {
        $sql = <<<DEVSXE
            SELECT
                COUNT(*) AS total
            FROM `wx_robot_reply`
            WHERE `type` = '{$type}'
            AND `is_del` = '0'
            {$where}
DEVSXE;
        $result = $this->db->g($sql, array());
        return $result[0]['total'];
    }

    /**
     * 添加自动回复内容
     */
    public function add($params)
    {
        $result = $this->c('wx_robot_reply', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 更新回复状态
     */
    public function update($id, $params)
    {
        if (empty($params) || empty($id)) {
            return false;
        }

        if (isset($params['id'])) {
            unset($params['id']);
        }

        $result = $this->db->u('wx_robot_reply', $params, $id);
        return $result;
    }

    /**
     * 获取关键词列表
     *
     * @param  string  $fields 字段
     * @param  string  $where  查询条件
     * @param  string  $order  排序
     * @param  string  $limit  列数
     * @return array
     */
    public function searchKeyword($fields, $where, $order, $limit)
    {
        $search = array();
        $sql = "SELECT $fields FROM `wx_robot_keyword` $where $order $limit";
        $params = array();
        $result['list'] = array();
        $list = $this->db->g($sql, $params);
        if (!empty($list)) {
            $result['list'] = $list;
        }

        $sql = "SELECT count(`id`) as `total` FROM `wx_robot_keyword` $where ";
        $total = $this->db->g($sql, $params);
        $result['total'] = $total[0]['total'];
        return $result;
    }

    /**
     * 添加关键词
     */
    public function addKeyword($params)
    {
        $result = $this->c('wx_robot_keyword', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 根据id获取关键词信息
     */
    public function getKeywordInfo($id)
    {
        $sql = "SELECT * FROM `wx_robot_keyword` WHERE `id` = '{$id}'";
        $result = $this->db->g($sql, array());
        return $result[0];
    }

    /**
     * 更新关键词信息
     */
    public function updKeyword($id, $params)
    {
        if (isset($params['id'])) {
            unset($params['id']);
        }
        $result = $this->db->u('wx_robot_keyword', $params, $id);
        return $result;
    }

}
