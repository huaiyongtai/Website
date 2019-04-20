<?php

/**
 *  消息任务模型  
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class MsgTask extends Model
{

    protected $db = null;
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }
    
    public function get($id)
    {
        $sql = <<<DEVSXE
            SELECT *
            FROM wx_msg_task
            WHERE id = {$id}
            LIMIT 1
DEVSXE;
        $result = $this->db->g($sql, array());
        return empty($result) ? array() : $result[0];
    }
    


    /**
     * 获取下一个待执行的任务
     * @param type $type
     */
    public function nextTaskType($type)
    {
        $date = date('Y-m-d H:i:s');
        $sql = <<<DEVSXE
            SELECT *
            FROM wx_msg_task
            WHERE type = {$type}
            AND `status` = 1
            AND `start_time` <= '{$date}'
            ORDER BY priority DESC
            LIMIT 1
DEVSXE;
        $result = $this->db->g($sql, array());
       return empty($result) ? array() : $result[0];
    }
    
    /**
     * 获取对应任务类型的所有任务
     * @param type $type
     * @return type
     */
    public function tasksByType($type)
    {
        $date = date('Y-m-d H:i:s');
        $sql = <<<DEVSXE
            SELECT *
            FROM wx_msg_task
            WHERE type = {$type}
            AND `status` = 1
            AND `start_time` <= '{$date}'
            ORDER BY priority DESC, id
DEVSXE;
        $result = $this->db->g($sql, array());
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
        $result = $this->db->u('wx_msg_task', $params, $id);

        return $result;
    }

    /**
     * 添加任务
     * @param $params 在更新数据时params参数应包含对应的id字段，否则将更新失败
     * @return boolean
     */
    public function add($params)
    {
        if (empty($params['start_time']) || empty($params['content'])) {
            return false;
        }

        $params['create_time'] = date('Y-m-d H:i:s');
        $result = $this->db->c('wx_msg_task', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 获取任务列表
     * @return array 返回符合条件的任务
     */
    public function taskList($start, $length, $where = 'WHERE 1=1')
    {
        if (!isset($start) || !isset($length)) {
            return array();
        }

        $sql = 'SELECT * FROM wx_msg_task ' . $where . ' ORDER BY id DESC LIMIT ' . $start . ', ' . $length;
        $result = $this->db->g($sql, array());
        return $result;
    }

    /**
     * 获取任务总数
     * @return int 返回任务总数
     */
    public function taskTotal($where = 'WHERE 1=1')
    {
        $sql = 'SELECT COUNT(*) AS total FROM wx_msg_task ' . $where;
        $result = $this->db->g($sql, array());
        return $result[0]['total'];
    }

}
