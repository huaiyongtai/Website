<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class SceneQRCode extends Model
{
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }
    
    /**
     * 查找指定二维码
     * @param int $id 场景id
     * @return 失败->false, 成功->整个记录
     */
    public function get($id)
    {
        if (empty($id)) {
            return false;
        }
        
        $sql = 'SELECT * FROM wx_scene_qrcode WHERE id = :id';
        $params = array(array('id', $id, \PDO::PARAM_INT));
        $result = $this->db->g($sql, $params);
        if (empty($result)) {
            return false;
        }
        
        return $result[0];
    }
    
    /**
     * 获取指定应用的最后一个id
     * @param int $type 使用类型
     * @return 场景id
     */
    public function getLastSceneIdByType($type)
    {
        if (empty($type)) {
            return false;
        }
        
        $sql = 'SELECT scene_id from weixin.wx_scene_qrcode WHERE type = :type ORDER BY id DESC LIMIT 1';
        $params = array(array('type', $type, \PDO::PARAM_INT));
        $result = $this->db->g($sql, $params);
        if (empty($result)) {
            return false;
        }
        
        return $result[0]['scene_id'];
    }
    
    /**
     * 获取指定应用的最后一个id
     * @param int $type 使用类型
     * @return 场景id
     */
    public function getCountByType($type)
    {
        if (empty($type)) {
            return false;
        }
        
        $sql = 'SELECT COUNT(*) AS total from weixin.wx_scene_qrcode WHERE type = :type';
        $params = array(array('type', $type, \PDO::PARAM_INT));
        $result = $this->db->g($sql, $params);
        if (empty($result)) {
            return false;
        }
        
        return $result[0]['total'];
    }

    /**
     * 添加二维码信息
     * @param array $params 二维码信息
     * @return 添加成功 返回自增id, 失败 false
     */
    public function add($params)
    {
        $params['update_time'] = date('Y-m-d H:i:s');
        $params['create_time'] = date('Y-m-d H:i:s');
        
        $result = $this->c('wx_scene_qrcode', $params);

        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 修改二维码信息
     * @param $params 在更新数据时params参数应包含对应的id字段，否则将更新失败
     * @return boolean
     */
    public function update($params)
    {
        $id = $params['id'];
        if (empty($id)) {
            return false;
        }
        
        //禁止跟新的字段信息
        unset($params['id']);
        unset($params['url']);
        unset($params['ticket']);
        unset($params['scene_id']);
        unset($params['create_name']);
        unset($params['create_time']);

        $params['update_time'] = date('Y-m-d H:i:s');
        $result = $this->db->u('wx_scene_qrcode', $params, $id);

        return $result;
    }

    /**
     * 根据二维码标题查找
     * @param string $title 二维码标题
     * @return 名称对应的记录
     */
    public function getByTitle($title)
    {
        $sql = 'SELECT * FROM wx_scene_qrcode WHERE title = :title';
        
        $params = array(array('title', $title, \PDO::PARAM_STR));
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 获取指定二维码列表
     * @param int $offset 起始位置
     * @param int $length 查找长度
     * @param int $type   使用类型 1->内部使用， 2->运营
     * @return array 二维码列表信息
     */
    public function getList($offset, $length, $type)
    {
        $sql = 'SELECT * FROM wx_scene_qrcode WHERE type = :type ORDER BY id DESC LIMIT :offset, :len';
        $len = (int)$length;
        $params = array(
            array('type', $type, \PDO::PARAM_INT),
            array('len', $len, \PDO::PARAM_INT),
            array('offset', $offset, \PDO::PARAM_INT),
        );
        
        $result = $this->db->g($sql, $params);

        return $result;
    }

    /**
     * 获取数据总数
     */
    public function totalCount()
    {
        $sql = 'SELECT COUNT(*) AS total FROM wx_scene_qrcode';
        $result = $this->db->g($sql, array());

        return $result[0]['total'];
    }
}

    