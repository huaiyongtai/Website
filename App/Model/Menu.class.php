<?php

/**
 * 微信后台模型，常用的增删该查操作
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Menu extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    //*********************增删改查*********************//
    /**
     * 添加
     * @return bool
     */
    public function add($params)
    {
        $params['update_time'] = date('Y-m-d H:i:s');
        $params['create_time'] = date('Y-m-d H:i:s');

        $result = $this->c('wxadmin_menus', $params);

        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 根据id删除
     * @param $id id为空删除所有， id不为空删除对应的数据
     * @return bool
     */
    public function del($id)
    {
        $sql = 'DELETE FROM wxadmin_menus';
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
        $result = $this->db->u('wxadmin_menus', $params, $id);

        return $result;
    }

    /**
     * 查找
     * @param $id $id查找对应的节点
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM `wxadmin_menus` WHERE `id` = ' . $id;
        $result = $this->db->g($sql, array());
        return $result;
    }
	
	/**
     * 查找所有节点
     * @return 失败->false, 成功->整个记录
     */
    public function allNodes()
    {
        $sql = 'SELECT * FROM `wxadmin_menus` ORDER BY `id` ASC';
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 根据名称查找
     * @return 失败->false, 成功->与名称对应的记录
     */
    public function getByName($name)
    {
        $sql = 'SELECT * FROM wxadmin_menus WHERE name = ' . "'$name'";
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 获取所有父节点信息
     * @return 失败->false, 成功->父节点记录
     */
    public function getPNodes()
    {
        $sql = 'SELECT * FROM wxadmin_menus WHERE pid = 0';
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 获取对应父节点的子节点个数
     */
    public function getSubNodeCount($pid)
    {
        $sql = 'SELECT COUNT(*) FROM wxadmin_menus WHERE pid = ' . "'$pid'";
        $result = $this->db->g($sql, array());

        return $result[0]['count(*)'];
    }

    //==================

    /**
     * 获取多条映射关系对应的记录
     */
    public function getMaps($tbName, $maps)
    {
        $sql = 'SELECT * FROM ' . $tbName . ' WHERE ';
        $joinStr = ' AND ';
        foreach ($maps as $key => $value) {
            $sql .= ($key . ' = ' . "'$value'");
            $sql .= $joinStr;
        }

        $sqlStr = substr($sql, 0, -strlen($joinStr));
        $result = $this->db->g($sqlStr, array());

        return $result;
    }

    /**
     * 获取多个Id对应的记录
     * @return 失败->false, 成功->符合条件的记录
     */
    public function getByIds($tbName, $ids)
    {
        $map = array('id' => $ids);
        return $this->getByFieldsMap($tbName, $map);
    }

    /**
     * 查找符合map映射的数据
     * @param type $map
     * @return boolean
     */
    public function getByFieldMap($tbName, $map)
    {
        if (!is_array($map)) {
            return false;
        }

        $key = key($map);
        $value = $map[$key];
        $sql = 'SELECT * FROM ' . $tbName . ' WHERE ' . $key . ' = ' . "'$value'";
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 获取多个字段对应的记录
     * $map array('id' => array(1, 2, 3, 4, ....))
     */
    public function getByFieldsMap($tbName, $map)
    {
        if (!is_array($map)) {
            return false;
        }

        $key = key($map);
        $value = (is_array($map[$key]) ? implode(', ', $map[$key]) : $map[$key]);
        $sql = ('SELECT * FROM ' . $tbName . ' WHERE ' . $key . ' IN (' . $value . ')');
        $result = $this->db->g($sql, array());

        return $result;
    }

    /**
     * 获取数据总数
     */
    public function totalCount()
    {
        $sql = 'SELECT COUNT(*) as total FROM wxadmin_menus';
        $result = $this->db->g($sql, array());

        return $result[0]['total'];
    }

    /**
     * 是否存在字段
     * @return boolean
     */
    public function isExistField($tbName, $map)
    {
        if (!is_array($map)) {
            return false;
        }

        $key = key($map);
        $value = $map[$key];
        $sql = 'SELECT COUNT(' . $key . ') FROM ' . $tbName . ' WHERE ' . $key . ' = ' . "'$value'";
        $result = $this->db->g($sql, array());

        return $result[0]["count($key)"];
    }

    /**
     * 获取指定节点级别
     * @param nodeId 节点id
     * @return 节点所处级别
     */
    public function getLevelNode($nodeId)
    {
        $levelNum = -1;
        $pid = -1;
        while ($pid != 0) {

            $sql = "SELECT pid FROM wxadmin_menus WHERE id = '$nodeId'";
            $result = $this->db->g($sql, array());
            if (!is_array($result[0])) {
                break;
            }
            $pid = $result[0]['pid'];
            $nodeId = $pid;
            $levelNum++;
        }
        return $levelNum;
    }

}
