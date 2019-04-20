<?php

/**
 * 微信-回复提示信息
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Activity extends Model
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

        $result = $this->c('wx_activity', $params);

        return $result ? $this->getLastInsertId() : false;
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
        $result = $this->db->u('wx_activity', $params, $id);

        return $result;
    }

    /**
     * 查找
     * @param $id id为空查找所有， $id不为空查找对应的
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM wx_activity';
        $sql .= empty($id) ? ' ORDER BY id ASC' : (' WHERE Id = ' . "'$id'");
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 根据名称查找
     * @return 失败->false, 成功->与名称对应的记录
     */
    public function getByName($name)
    {
        $sql = 'SELECT * FROM wx_activity WHERE name = ' . "'$name'";
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 获取数据列表
     */
    public function getList($offset, $length)
    {
        $sql = 'SELECT * FROM wx_activity ORDER BY id DESC LIMIT ' . $offset . ', ' . $length;
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 获取数据总数
     */
    public function totalCount()
    {
        $sql = 'SELECT COUNT(*) FROM wx_activity';
        $result = $this->db->g($sql, array());

        return $result[0]['count(*)'];
    }

}
