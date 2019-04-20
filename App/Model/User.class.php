<?php

/*
 * 微信用户信息
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class User extends Model
{
    protected $db = null;
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 添加一个用户
     * @param array $params 用户信息数组
     * @return 添加成功 int 自增id, 失败 bool false
     */
    public function add($params)
    {
        if (empty($params['user_id'])) {
            return false;
        }

        $result = $this->c('wx_user_info', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 更新用户信息
     * @param array $params 更新信息
     * @return boolean
     */
    public function updateByUserId($params)
    {
        $id = $params['user_id'];
        if (!isset($id)) {
            return false;
        }
        unset($params['user_id']);
        $result = $this->db->u('wx_user_info', $params, $id, 'user_id');
        return $result;
    }

    /**
     * 查找用户
     * @param $userId userId为空查找所有， $userId不为空查找对应的
     * @return 失败->false, 成功->整个记录
     */
    public function get($wxId, $userId)
    {
        $sql = <<<DEVSXE
            SELECT *
            FROM `wx_user_info`
            WHERE `user_id` = :userId
            AND `wx_id` = :wxId
            LIMIT 1
DEVSXE;
        $params = array(
            array('wxId', $wxId, \PDO::PARAM_STR),
            array('userId', $userId, \PDO::PARAM_STR),
        );
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 根据学生id值获取发送模板消息时所需的信息
     * @param 可直接传入学生id数组 或 学生id
     */
    public function getOpenIdsByStuIds($stuIds)
    {
        if (empty($stuIds)) {
            return false;
        }
        $idStr = is_array($stuIds) ? implode(',', $stuIds) : $stuIds;

        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\default');
        $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);

        $sqlPlat = <<<DEVSXE
            SELECT stu_id, plat_user_id
            FROM devsxe_platform_users
            WHERE plat_id = 8 AND bind_status = 1 AND stu_id IN ({$idStr})
DEVSXE;
        $platInfos = $db->g($sqlPlat, array());
        if (empty($platInfos)) {
            return array();
        }
        $ids = array();
        foreach ($platInfos as $value) {
            $ids[] = $value['stu_id'];
        }

        $stuIdStr = implode(',', $ids);
        $sqlStu = <<<DEVSXE
            SELECT id, cur_grade, realname
            FROM devsxe_students
            WHERE id IN ({$stuIdStr})
DEVSXE;
        $stuInfos = $db->g($sqlStu, array());
        if (empty($stuInfos)) {
            return array();
        }
        $result = array();
        foreach ($platInfos as $platVal) {
            foreach ($stuInfos as $stuVal) {
                if ($platVal['stu_id'] != $stuVal['id']) {
                    continue;
                }
                $result[] = array(
                    'cur_grade' => $stuVal['cur_grade'],
                    'name' => $stuVal['realname'],
                    'openId' => $platVal['plat_user_id'],
                );
                break;
            }
        }
        return $result;


//        $sql = <<<DEVSXE
//            SELECT a.cur_grade, a.realname, b.plat_user_id
//            FROM devsxe_students a INNER JOIN devsxe_platform_users b
//            ON a.id = b.stu_id AND b.plat_id = 8 AND b.bind_status = 1
//            WHERE a.id IN ({$idStr})
//            ORDER BY create_time DESC;
//DEVSXE;
//
//        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\default');
//        $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
//
//        $result = $db->g($sql, array());
//        return $result;
    }

    /**
     * 获取用户的绑定信息
     * @param int $id 可以为openId,也可以为学生id，默认为OpenId
     * @param int $idType 依赖id类型，$id为学生Id $idType=2, $id为OpenId $idType=1
     * @return array 绑定信息
     */
    public function getBindInfoById($id, $idType = 1, $status = 1)
    {
        if (empty($id)) {
            return false;
        }
        $sql = <<<DEVSXE
            SELECT * FROM wss.devsxe_platform_users
            WHERE plat_id = :platId AND bind_status = :status AND
DEVSXE;
        $sql .= ($idType == 1 ? ' plat_user_id = :id' : ' stu_id = :id');

        $params = array(
            array('id', $id, \PDO::PARAM_STR),
            array('platId', 8, \PDO::PARAM_INT),
            array('status', $status, \PDO::PARAM_INT),
        );

        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\slave');
        $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);

        $result = $db->g($sql, $params);
        if (empty($result)) {
            return array();
        }
        return $result[0];
    }

    /**
     * 根据学生Id判断是否是微信绑定用户
     * @param int $id 可直接传入openId 或 学生id数组id
     * @param array $idType id的类型， openId->1, stuId->2
     */
    public function isBindWx($id, $idType)
    {
        if (empty($id)) {
            return false;
        }
        $sql = <<<DEVSXE
            SELECT * FROM wss.devsxe_platform_users
            WHERE plat_id = 8 AND bind_status = 1 AND
DEVSXE;
        $sql .= ($idType == 1 ? ' plat_user_id' : ' stu_id');
        $sql .= '=' . "'$id'";
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\default');
        $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);

        $result = $db->g($sql, array());
        return empty($result) ? false : true;
    }

    /**
     * 查找绑定的学生
     * @param array $stuIds 待学生Id数组
     * @return 绑定的学生Id数组
     */
    public function checkBindWx($stuIds)
    {
        if (empty($stuIds)) {
            return false;
        }
        $idStr = is_array($stuIds) ? implode(',', $stuIds) : $stuIds;
        $sql = <<<DEVSXE
            SELECT stu_id
            FROM devsxe_platform_users
            WHERE plat_id = 8 AND bind_status = 1 AND stu_id IN ($idStr)
DEVSXE;
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\default');
        $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
        $result = $db->g($sql, array());

        $bindStu = array();
        foreach ($result as $value) {
            $bindStu[] = $value['stu_id'];
        }
        return $bindStu;
    }

    //下面接口绑定状态
    /**
     * 获取用户的成绩信息
     * @param string $stuName 学生名
     * @param int $status 成绩状态 0 = 有效， 1 = 失效， -1 = 所有
     */
    public function getScore($stuName, $status = 0)
    {
        if (!isset($stuName)) {
            return array();
        }

        $sql = 'SELECT * FROM wx_score WHERE name = :name';
        if ($status != -1) {
            $sql .= (' AND status = :status');
            $params[] = array('status', $status, \PDO::PARAM_INT);
        }

        $params[] = array('name', $stuName, \PDO::PARAM_STR);

        return $this->db->g($sql, $params);
    }

    /**
     * 获取用户的订单信息
     * @param string $stuName 学生名
     * @param int $status 订单状态 0 = 已发送， 1 = 已完成， -1 = 所有
     */
    public function getExpress($stuName, $status = 0)
    {
        if (!isset($stuName)) {
            return array();
        }

        $params = array();
        $sql = 'SELECT * FROM wx_order WHERE name = :name';
        if ($status != -1) {
            $sql .= (' AND status = :status');
            $params[] = array('status', $status, \PDO::PARAM_INT);
        }

        $params[] = array('name', $stuName, \PDO::PARAM_STR);

        return $this->db->g($sql, $params);
    }

    /**
     * 根据分组名称 获取分组信息
     * @param string $name 分组名称
     * @return array 分组信息
     */
    public function getGroupByName($name)
    {
        $sql = 'SELECT * FROM wx_group_info WHERE name = :name';
        $params = array(
            array('name', $name, \PDO::PARAM_STR),
        );
        $result = $this->db->g($sql, $params);

        if (empty($result)) {
            return array();
        }
        return $result[0];
    }

    /**
     * 根据条件获取特定用户
     * @param  string $where 条件字符串
     * @return array 用户信息
     */
    public function userCountByFilter($where)
    {
        $sql = 'SELECT COUNT(*) AS total FROM wx_user_info ' . $where;
        $sql .= ' AND user_type = 1 LIMIT 1';
        $result = $this->db->g($sql, array());
        return $result[0]['total'];
    }

    /**
     * 根据条件获取特定用户
     * @param  string $where 条件字符串
     * @return array 用户信息
     */
    public function userIdsByFilter($where, $start = 0, $length = 1000000)
    {
        $sql = 'SELECT user_id FROM wx_user_info ' . $where;
        $sql .= ' AND user_type = 1';
        $sql .= ' LIMIT ' . $start .', ' . $length;
        $result = $this->db->g($sql, array());
        return $result;
    }

}
