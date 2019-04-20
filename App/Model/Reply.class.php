<?php

/**
 * 微信-回复提示信息
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Reply extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 添加
     * @return bool
     */
    public function add($params)
    {
        $params['update_time'] = date('Y-m-d H:i:s');
        $params['create_time'] = date('Y-m-d H:i:s');

        $result = $this->c('wx_reply', $params);

        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 根据id删除
     * @param $id id为空删除所有， id不为空删除对应的数据
     * @return bool
     */
    public function del($id)
    {
        $sql = 'DELETE FROM wx_reply';
        if (!empty($id)) {
            $sql .= ' WHERE id  = ' . "'$id'";
        }
        $result = $this->db->ud($sql, array());

        return $result;
    }

    /**
     * 修改表中d对应数据
     * @param $params 在更新数据时params参数应包含对应的id字段，否则将更新失败
     * @return boolean
     */
    public function update($params)
    {
        $id = $params['id'];
        if (empty($id)) {
            return false;
        }
        unset($params['id']);

        $params['update_time'] = date('Y-m-d H:i:s');
        $result = $this->db->u('wx_reply', $params, $id);

        return $result;
    }

    /**
     * 查找
     * @param $id id为空查找所有， $id不为空查找对应的
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM wx_reply';
        $sql .= empty($id) ? ' ORDER BY id ASC' : (' WHERE Id = ' . "'$id'");
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 根据关键词kw查找
     * @return 失败->false, 成功->与名称对应的记录
     */
    public function getByKW($wxId, $kw, $status)
    {
        $params = [
            ['wxId', $wxId, \PDO::PARAM_STR],
            ['content', $kw, \PDO::PARAM_STR],
        ];

        $where = ' AND 1=1 ';
        if ($status != -1) {
            $where .= ' AND `status` = :status ';
            $params[] = ['status', $status, \PDO::PARAM_INT];
        }
        $sql = <<<DEVSXE
            SELECT *
            FROM `wx_reply`
            WHERE `wx_id` = :wxId
            AND `content` = :content
            {$where}
            LIMIT 1
DEVSXE;
        $result = $this->db->g($sql, $params);
        return empty($result) ? array() : $result[0];
    }

    /**
     * 获取数据列表
     */
    public function getList($wxId, $offset, $length)
    {
        $sql = <<<DEVSXE
            SELECT *
            FROM `wx_reply`
            WHERE `wx_id` = :wxId
            ORDER BY `id` DESC
            LIMIT :offset, {$length}
DEVSXE;
        $params = array(
            array('wxId', $wxId, \PDO::PARAM_STR),
            array('offset', $offset, \PDO::PARAM_INT),
        );
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 获取数据总数
     */
    public function totalCount($wxId)
    {
        $sql = 'SELECT COUNT(*) as total FROM wx_reply WHERE `wx_id` =:wxId';
        $params = array(
            array('wxId', $wxId, \PDO::PARAM_STR),
        );
        $result = $this->db->g($sql, $params);

        return $result[0]['total'];
    }

    /**
     * 根据规则的开启关闭规则获取回复提示信息
     * status: 1, 开启
     *         2，关闭
     */
    public function getRepliesByStatus($wxId, $status)
    {
        $sql = <<<DEVSXE
            SELECT
                `content`
            FROM
                `wx_reply`
            WHERE
                `wx_id` = :wxId
            AND `status` = :status
            ORDER BY
                `id` DESC
DEVSXE;
        $params = array(
            array('wxId', $wxId, \PDO::PARAM_STR),
            array('status', $status, \PDO::PARAM_INT),
        );
        $result = $this->db->g($sql, $params);
        return $result;
    }

}
