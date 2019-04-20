<?php

/**
 * 微信-消息发送处理
 *      type 1. 
 */

namespace DevSxe\Application\Controller\WeiXin;

use \DevSxe\Application\Controller\AppController;

class Message extends AppController
{
    /**
     * 功能是消息发送
     */
    public function sendMsg() {
     
        $params = $this->params['msgInfo'];
        $msgC = $this->_msgFactory($params['type']);
        if ($msgC == false) {
            return array(
                'stat' => -1001,
                'data' => 'missing paramter type'
            );
        }
        
        $result = $msgC->sendRawMsg($params['stuId'], $params['msg']);
        return $result;
    }
    
    /**
     * 
     * @param type $type
     * @return boolean
     */
    private function _msgFactory($type)
    {        
        $msgName = '';
        switch ($type) {
            case 1:
                $msgName = 'TemplateMsg';
                break;
        }
        
        if (empty($msgName)) {
            return false;
        }
        return \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\\' . $msgName);
    }
}
