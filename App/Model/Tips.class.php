<?php

/**
 * 微信-回复提示信息
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Tips extends Model
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

        $result = $this->c('wx_tips', $params);

        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 根据id删除
     * @param $id id为空删除所有， id不为空删除对应的数据
     * @return bool
     */
    public function del($id)
    {
        $sql = 'DELETE FROM wx_tips';
        if (!empty($id)) {
            $sql .= 'WHERE id  = ' . "'$id'";
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
        $result = $this->db->u('wx_tips', $params, $id);

        return $result;
    }

    /**
     * 查找
     * @param $id id为空查找所有， $id不为空查找对应的
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM wx_tips';
        $sql .= empty($id) ? ' ORDER BY id ASC' : (' WHERE id = ' . "'$id'");
        $result = $this->db->g($sql, array());

        return $result;
    }
    
    /**
     * 查找开启状态提示
     * @param int $id
     * @return 失败->false, 成功->整个记录
     */
    public function getValidById($id)
    {
        $sql = 'SELECT * FROM wx_tips WHERE status = 2 AND id = ' . "'$id'";
        $result = $this->db->g($sql, array());

        return $result;
    }
    
    /**
     * 根据标题查找
     * @return 失败->false, 成功->与名称对应的记录
     */
    public function getByTitle($title)
    {
        $sql = 'SELECT * FROM wx_tips WHERE title = ' . "'$title'";
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 获取数据列表
     */
    public function getList($offset, $length)
    {
        $sql = 'SELECT * FROM wx_tips ORDER BY id DESC LIMIT ' . $offset . ', ' . $length;
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 获取数据总数
     */
    public function totalCount()
    {
        $sql = 'SELECT COUNT(*) FROM wx_tips';
        $result = $this->db->g($sql, array());

        return $result[0]['count(*)'];
    }
    
    /**
     * 通过类别获取已开启的回复规则记录
     * @return 失败->false, 成功->父节点记录
     */
    public function getOpenedByCategory($category)
    {
        $sql = 'SELECT * FROM wx_tips WHERE status = 2 AND category = ' . "'$category'";
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 获取菜单推送信息
     * @return 失败->false, 成功->父节点记录
     */
    public function getMenuPushMsg()
    {
        $sql = 'SELECT id, title FROM wx_tips WHERE category = 4 AND status = 2';
        $result = $this->db->g($sql, array());

        return $result;
    }

}
