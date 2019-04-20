<?php

/**
 * 微信50元推广用户行为记录日志
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class UserLog extends Model
{
    protected $db = null;
    // 表名
    const dbName = 'wx_user_logs';
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

	//记录日志
	public function addLog($param){
		$table = self::dbName;	
		$params = array(
			'phoneNum' =>isset($param['phoneNum'])?$param['phoneNum']:'',
			'createTime'=>date('Y-m-d H:i:s'),
		);
		$result = $this->db->c($table,$params);
		return $result;
	
	}

}
