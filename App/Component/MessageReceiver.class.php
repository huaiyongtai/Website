<?php

/*
 * 普通消息接收处理
 *    目前只支持对文本消息的回复
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Storage;
use DevSxe\Application\Model\Wx\UserLogs;
class MessageReceiver extends Receiver
{

    /**
     * 消息处理入口
     * @param array $rawMsg 原始消息
     */
    public function process($rawMsg)
    {
        switch ($rawMsg['MsgType']) {
            case "text":
                return $this->textProcess($rawMsg);
            case "voice":
                return $this->voiceProcess($rawMsg);
            case "image":
            case "video":
            case 'shortvideo':
            case 'location':
            case 'link':
                return '';
            default :
                return '';
        }
    }
    /**
     * 50元课老带新海报
     * @param $rawMsg
     */
    private function fiftyCreateQrcode($rawMsg)
    {
        fastcgi_finish_request();
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken('default');
        $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
        $openid =$rawMsg['FromUserName'];
        $stuId =$rawMsg['userid'];
        if (empty($stuId)) {
            //账号未绑定
            $msg = "绑定开发测试账号后，点击菜单栏-50元课-领奖活动 即可参加领奖活动哦~
<a href=\"https://login.domain.com/\">点击绑定开发测试账号</a>
温馨提示：初始密码是您手机号的后6位哦！
开发测试24小时客服热线 400-800-2211";
            $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
            $CustomerMesC->eCardNoAccountMsg($token,$openid,$msg);
            exit;
        }
        // 已有二维码直接回复
        $userInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid);
        $now = time();
        $creatTime = !empty($userInfo['create_time']) ? $userInfo['create_time'] : '';
        $endTime = !empty($userInfo['end_time']) ? $userInfo['end_time'] : '';
            $media_id = $userInfo['media_id'];
            $msg = '主讲百里挑一，私教千人千面
预习有人讲，作业有人改
课堂互动多，好货可回放

语文、数学、英语， 50元10天双师直播课正在火热抢购中
<a href="http://www.domain.com/devsxe.php?source=79412802&site_id=383&adsite_id=474403">点击立即抢购></a>';
            $CustomerMesC->eCardNoAccountMsg($token,$openid,$msg);
            $CustomerMesC->sendPic($token,$openid,$media_id);
            unset($params);
            $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');
            $params['openid'] = $openid;
            $content = $rawMsg['Content'];
            $params['action'] = 'input_Ecard_' . $content;
            $params['reply'] = 'creatQRcode_alrealy_hava';
            $params['reply_user_openid'] = $openid ;
            $logM->addLog($params);
            return [];

    }
    /**
     * 文本消息处理
     * @return array 消息回复内容
     */
    public function textProcess($rawMsg)
    {
        //用户回复50元课发送2条客服消息
        if(strpos($rawMsg['Content'], '50元课')!==FALSE){
            $this->fiftyCreateQrcode($rawMsg);
        }
        $replyRes = \DevSxe\Lib\G('DevSxe\Application\Component\Op\Invite\CubeCourse')->magicCubeReplay($rawMsg);
        if($replyRes !== false){
            return $replyRes;
        }
        $replyRes = \DevSxe\Lib\G('DevSxe\Application\Component\Op\Invite\OrigamiCourse')->reply($rawMsg);
        if($replyRes !== false){
            return $replyRes;
        }
        //用户分组
        if ($rawMsg['ToUserName'] ==  Account::defaultWxId()) {
            $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
            $groupInfo = $userC->getGroupByName($rawMsg['Content']);
            if ($groupInfo) {
                //更新用户分组信息
                $userC->updateGruop($rawMsg['ToUserName'], $rawMsg['FromUserName'], $groupInfo['id']);
            }
        }
        $responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        $resp = $responserC->replyForText($rawMsg);
        return array($resp);
    }

    /**
     * yu
     * @param type $rawMsg
     * @return array 消息回复内容
     */
    public function voiceProcess($rawMsg)
    {
        //kefu
        if ($rawMsg['ToUserName'] !=  Account::defaultWxId()) {
            return '';
        }

        $responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        $resp = $responserC->replyForVoice($rawMsg);
        return array($resp);
    }

}
