<?php

/**
 * 接受消息模型
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Receiver extends Model
{
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }
    
    
    /**
     * 保存消息到本地数据表
     * @param array $msg 带保存的消息
     * @param int   $type   保存消息类型 目前这有 1 
     * @return 保存成功后返回自增id 失败返回false
     */
    public function saveMsg($msg, $type = 1)
    {
        $msg['create_time'] = date('Y-m-d H:i:s');
        
        $tbName = $this->_tbNameByType($type);
        $result = $this->c($tbName, $msg);

        return $result ? $this->getLastInsertId() : false;
    }
    
    /**
     * 根据类型确定表明
     * @param type $type
     * @return string 数据表名
     */
    private function _tbNameByType($type) {
        switch ($type) {
            case 1:
                return 'wx_msg_main';
        }
    }
    
}
