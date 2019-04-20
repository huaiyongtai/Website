<?php

/*
 * 微信-客服消息
 */

namespace DevSxe\Application\Component\Wx;

class CustomServiceMsg extends Message
{

    /**
     * 发送客服消息
     * @param string $openId  接受的OpenId
     * @param string $msgType 客服消息类型
     * @param array  $content 消息内容数组
     * @param int    $type    消息类型  1 发送测试 2 发送线上
     */
    public function sendMsg($wxId, $openId, $cusMsg)
    {
        $msgInfo = [];
        switch ($cusMsg['type']) {
            case 1:
                $msgInfo['msgtype'] = 'text';
                $msgInfo['text'] = $cusMsg['content'];
                break;
            case 2:
                $msgInfo['msgtype'] = 'news';
                $msgInfo['news'] = $cusMsg['content'];
                break;
            case 3:
                $msgInfo['msgtype'] = 'image';
                $msgInfo['image'] = $cusMsg['content'];
                break;
            default:
                return ['stat' => 0, 'data' => '暂不支持此类型的客服消息'];
        }

        $msgInfo['touser'] = $openId;
        return $this->send(3002, $msgInfo, $wxId);
    }

    public function sendText($openId, $content, $wxId = null)
    {
        if($wxId === null){
            $wxId = $token = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Account')->defaultWxId();
        }
        $cusMsg = [
            'type' => '1',
            'content' => [
                'content' => $content
            ],
        ];
        return $this->sendMsg($wxId, $openId, $cusMsg);
    }

    public function sendImage($openId, $mediaId, $wxId = null)
    {
        if($wxId === null){
            $wxId = $token = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Account')->defaultWxId();
        }
        $cusMsg = [
            'type' => '3',
            'content' => [
                'media_id' => $mediaId
            ],
        ];
        return $this->sendMsg($wxId, $openId, $cusMsg);
    }
}
