<?php

/**
 * 事件和消息接收基类
 */

namespace DevSxe\Application\Component\Wx;

abstract class Receiver
{

    private $msgLogId;

    /**
     * 处理消息
     * @param array $rawMsg 原始消息
     */
    abstract public function process($rawMsg);

    /**
     * 验证接受到的消息是否有效
     * @param array $rawMsg
     * @return bool 消息有效true 消息无效false
     */
    public function checkValidity($rawMsg)
    {
        if (empty($rawMsg['FromUserName'])) {
            return false;
        }
        if (empty($rawMsg['ToUserName'])) {
            return false;
        }
        return true;
    }

    /**
     * 消息存储
     * @param type $rawMsg
     */
    public function save($rawMsg)
    {
        //1. 矫正存储字段信息
        $msg = $this->_mapSaveFields($rawMsg);

        //2. 获取消息的其他关联信息
        $attachInfo = $this->_attachForMsg($rawMsg);

        //3. 信息合并
        $msgInfo = array_merge($msg, $attachInfo);

        //4. 存储
        $receiverM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Receiver');
        $msgLogId = $receiverM->saveMsg($msgInfo);
        if ($msgLogId) {
            $this->msgLogId = $msgLogId;
        }
    }

    /**
     * 消息本地存储Id
     */
    protected function getMsgLogId()
    {
        return $this->msgLogId;
    }

    /**
     * 消息发送者信息
     */
    private function _attachForMsg($rawMsg)
    {
        $nickName = '';

        $openId = $rawMsg['FromUserName'];
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $userInfo = $userC->getByOpenId($rawMsg['ToUserName'], $openId);

        if (empty($userInfo)) {
            $userInfo = $userC->getFromWxByOpenId($openId, $rawMsg['ToUserName']);
            $nickName = $userInfo['nickname'];
        } else {
            $nickName = $userInfo['nick_name'];
        }
        return array(
            'fromusertruename' => $nickName,
            'tousertruename' => '开发测试服务号',
            'create_by' => $openId,
            'from_type' => 1,
        );
    }

    /**
     * 将原始消息转化为数据库存储的信息
     * @param array $rawMsg 原始消息
     */
    private function _mapSaveFields($rawMsg)
    {
        $mapDb = array(
            //=============消息、事件头==============
            'ToUserName' => 'tousername',
            'FromUserName' => 'fromusername',
            'CreateTime' => 'createtime',
            'MsgType' => 'msgtype',
            //=================普通消息===============
            //1. 文本消息
            'Content' => 'content',
            //2. 图片消息
            'MediaId' => 'mediaid',
            'PicUrl' => 'picurl', //图片链接（由系统生成）
            //3. 语音消息
            'Format' => '', //语音格式
            'MediaId' => 'mediaid',
            'MediaID' => 'mediaid', //语音识别后
            'Recognition' => '', //语音识别结果，UTF8编码
            //4. 小视频消息/视频消息
            'MediaId' => 'mediaid',
            'ThumbMediaId' => 'thumbmediaid', //消息缩略图的媒体id
            //5. 地理位置消息
            'Location_X' => 'location_x', //地理位置维度
            'Location_Y' => 'location_y', //地理位置经度
            'Scale' => 'scale', //地图缩放大小
            'Label' => 'label', //地理位置信息
            //6. 链接消息
            'Title' => 'label', //消息标题
            'Description' => '', //消息描述
            'Url' => 'content', //消息链接
            //消息尾
            'MsgId' => 'msgid', //消息id，64位整型
            //=================事件===============
            //事件头
            'Event' => 'Event', //事件类型
            //1. 关注/取消关注事件
            //2. 扫描带参数二维码事件
            'EventKey' => 'EventKey', //事件KEY值
            'Ticket' => 'content', //二维码的ticket
            //3. 上报地理位置事件
            'Latitude' => 'location_x', //地理位置维度
            'Longitude' => 'location_y', //地理位置经度
            'Precision' => 'content', //地理位置精度
            //4. 自定义菜单事件
            'EventKey' => 'EventKey',
            //5. 模板消息回执
            'MsgID' => 'msgid', //消息id
            'Status' => 'Status', //用户消息接受状态
            //6. 群发消息回执
            'MsgID' => 'msgid', //消息id
            'Status' => 'Status', //用户消息接受状态
            'TotalCount' => '',
            'FilterCount' => '',
            'SentCount' => '',
            'ErrorCount' => '',
        );

        $dbMsg = array();
        foreach ($rawMsg as $key => $value) {
            if (empty($mapDb[$key])) {
                continue;
            }
            $dbMsg[$mapDb[$key]] = $value;
        }
        return $dbMsg;
    }

}
