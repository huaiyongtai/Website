<?php

/**
 * 微信推送接收者处理类
 */

namespace DevSxe\Application\Controller\WeiXin;

use \DevSxe\Application\Controller\AppController;

class Receiver extends AppController
{

    /**
     * 接收消息消息并响应
     */
    public function receive()
    {
        $rawMsg = $this->params['rawMsg'];
        if (!isset($rawMsg)) {
            return array(
                'stat' => 0,
                'data' => '未收到消息'
            );
        }

        $receiver = $rawMsg['MsgType'] == 'event' ? 'EventReceiver' : 'MessageReceiver';
        $receiverC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\\' . $receiver);

        //1. 校验消息的的完整性（消息发送者、消息接收者、消息）
        if (!$receiverC->checkValidity($rawMsg)) {
            return array(
                'stat' => 0,
                'data' => '该消息不完整',
            );
        }

        //2. 保存消息
        $receiverC->save($rawMsg);

        //3. 处理消息
        $reply = $receiverC->process($rawMsg);
        return array(
            'stat' => 1,
            'data' => array(
                'touser' => $rawMsg['FromUserName'],
                'fromuser' => $rawMsg['ToUserName'],
                'msgInfo' => $reply,
            ),
        );
    }

}
