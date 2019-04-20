<?php

/**
 * 微信-二维码
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class QRCode extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 根据学生Id和场景Id获取二维码
     * @param string $stuId
     * @param string $sceneId
     * @return 二维码记录
     */
    public function checkCode($stuId, $sceneId)
    {
        $sql = <<<DEVSXE
            SELECT id,ticket_id,expires_end FROM `wx_qr_code`
            where stu_id = {$stuId} and scene_id = {$sceneId};
DEVSXE;
        $result = $this->db->g($sql, array());
        if (empty($result)) {
            return false;
        }
        return $result[0];
    }

    /**
     * 添加新的二维码
     * @param array 二维码参数
     * @return 添加失败-> bool(false) 添加成功-> 返回Id
     */
    public function add($data)
    {
        $result = $this->db->c('wx_qr_code', $data);
        return $result ? $this->getLastInsertId() : false;
    }
    
    public function addScanLog($data)
    {
        $result = $this->db->c('wx_qr_code_logs', $data);
        return $result ? $this->getLastInsertId() : false;
    }

    /**
     * 更新二维码数据
     * @param array 修改的参数（注必须包含修改的Id字段）
     * @return boolean
     */
    public function updata($params)
    {
        $id = $params['id'];
        if (empty($id)) {
            return false;
        }
        unset($params['id']);
        $result = $this->db->u('wx_qr_code', $params, $id);
        return $result;
    }

}
