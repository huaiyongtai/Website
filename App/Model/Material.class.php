<?php

/*
 * 微信后台-图文信息
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Material extends Model
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

        $result = $this->c('wx_news_message', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 根据id删除
     * @param $id id为空删除所有， id不为空删除对应的数据
     * @return bool
     */
    public function del($id)
    {
        $sql = 'DELETE FROM wx_news_message WHERE id = ' . "'$id'";
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
        $result = $this->db->u('wx_news_message', $params, $id);

        return $result;
    }

    /**
     * 查找
     * @param int $id不为空查找对应的
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM wx_news_message';
        $sql .= empty($id) ? ' ORDER BY id ASC' : (' WHERE Id = ' . "'$id'");
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 根据关键词kw查找
     * @return 失败->false, 成功->与名称对应的记录
     */
    public function getByTitle($wxId, $title)
    {
        $sql = <<<DEVSXE
            SELECT *
            FROM `wx_news_message`
            WHERE `wx_id` = :wxId
            AND `title` = :title
DEVSXE;
        $params = array(
            array('wxId', $wxId, \PDO::PARAM_STR),
            array('title', $title, \PDO::PARAM_STR),
        );
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 获取数据列表
     */
    public function getList($wxId, $offset, $length)
    {
        $sql = <<<DEVSXE
            SELECT
                *
            FROM
                `wx_news_message`
            WHERE
                `wx_id` = :wxId
            ORDER BY
                `id` DESC
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
        $sql = <<<DEVSXE
            SELECT COUNT(*) AS total
            FROM `wx_news_message`
            WHERE `wx_id` = :wxId
DEVSXE;
        $params = [['wxId', $wxId, \PDO::PARAM_STR]];
        $result = $this->db->g($sql, $params);
        return $result[0]['total'];
    }

    /**
     * 可供选择图文信息
     * @return type
     */
    public function getCheckTitle($wxId)
    {
        $sql = <<<DEVSXE
            SELECT
                `id`, `title`
            FROM
                `wx_news_message`
            WHERE
                `wx_id` =:wxId
            ORDER BY
                `id` DESC
DEVSXE;
        $params = [['wxId', $wxId, \PDO::PARAM_STR]];
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 获取多个字段对应的记录
     * 'ids' = array(1, 2, 3, 4, ....)
     */
    public function getByIds($ids)
    {
        $values = is_array($ids) ? implode(',', $ids) : $ids;
        $sql = 'SELECT * FROM wx_news_message WHERE id IN (' . $values . ')';
        $result = $this->db->g($sql, array());

        return $result;
    }

}
