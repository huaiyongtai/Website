<?php

/**
 * 微信-Token
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class UserInfo extends Model
{


    protected $db = null;
    
    // 表名
    const dbName = 'wx_userinfo';
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }


    // 向学生信息表中添加一条记录
    public function add($param)
    {
        $table = self::dbName;
        $params = array(
            'openid' => isset($param['openid'])?$param['openid']:'',
            'realStuid' => isset($param['realStuid'])?$param['realStuid']:'',
            'parentOpenid' => isset($param['parentOpenid'])?$param['parentOpenid']:'',
            'tmpStuid' => isset($param['tmpStuid'])?$param['tmpStuid']:'',
            'createTime'=>date('Y-m-d H:i:s'),                      
        );

        $result = $this->db->c($table, $params);
        return $result;
    }

    // 查询绑定信息
    public function searchBindInfo($realStuid)
    {
        $sql = <<<DEVSXE
            select * 
            from `wx_userinfo` 
            where  `realStuid` =:realStuid 
            group by parentOpenid desc
DEVSXE;
        $params = array(
            array('realStuid', $realStuid, \PDO::PARAM_STR),
        );
        $searchRes = $this->db->g($sql, $params);
        return $searchRes;
    }

}
