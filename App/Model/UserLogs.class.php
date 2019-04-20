<?php

/**
 * 微信50元推广用户行为记录日志
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class UserLogs extends Model
{
    protected $db = null;
    // 表名
    const dbName = 'wx_useraction_logs';
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }


    // 向学生信息表中添加一条记录
    public function addLog($param)
    {
        $table = self::dbName;
        $params = array(
            'user_openid'=>isset($param['openid'])?$param['openid']:'',                   
            'user_action'=>isset($param['action'])?$param['action']:'',                   
            'reply'=>isset($param['reply'])?$param['reply']:'',                   
            'reply_user_openid'=>isset($param['reply_user_openid'])?$param['reply_user_openid']:'',                
            'creat_time'=>date('Y-m-d H:i:s'),                      
        );
        $result = $this->db->c($table, $params);
        return $result;
    }
}
