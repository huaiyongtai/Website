<?php

/**
 * 事件推送接收处理者
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Storage;
use DevSxe\Application\Model\Wx\UserLogs;
use DevSxe\Service\Storage\Upload;

class EventReceiver extends Receiver
{

    /**
     * 事件处理入口
     * @param array $rawMsg 原始消息
     */
    public function process($rawMsg)
    {
        if ($rawMsg['MsgType'] != 'event') {
            return '';
        }

        switch (strtolower($rawMsg['Event'])) {
            case 'subscribe':   //关注:
                return $this->subscribe($rawMsg);
            case 'unsubscribe': //取消关注
                return $this->unsubscribe($rawMsg);
            case 'scan':        //扫描二维码事件
                return $this->scan($rawMsg);
            case 'click':       //菜单点击事件
                return $this->click($rawMsg);
            default :   //以下事件暂不做处理
                //'view'
                //'location':
                break;
        }
    }

    /**
     * 关注时用户相关数据添加操作
     */
    public function subscribe($rawMsg)
    {
        //1. 添加、更新用户关注信息
        //---1.1 获取用户信息
        $openId = $rawMsg['FromUserName'];
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $userInfo = $userC->getFromWxByOpenId($openId, $rawMsg['ToUserName']);
        $data = array();
        if (empty($userInfo) || $userInfo['subscribe'] == 0) {
            $data = array(
                'user_id' => $openId,
                'language' => 'zh_CN',
                'subscribe_time' => date('Y-m-d H:i:s', time()),
                'subscribe' => '1',
                'user_type' => 1,
                'wx_id' => $rawMsg['ToUserName'],
            );
        } else {
            $data = array(
                'wx_id' => $rawMsg['ToUserName'],
                'user_id' => $openId,
                'nick_name' => $userInfo['nickname'],
                'head_info' => $userInfo['headimgurl'],
                'sex' => $userInfo['sex'],
                'city' => $userInfo['city'],
                'country' => $userInfo['country'],
                'province' => $userInfo['province'],
                'language' => $userInfo['language'],
                'subscribe_time' => date('Y-m-d H:i:s', $userInfo['subscribe_time']),
                'subscribe' => $userInfo['subscribe'],
                'user_type' => 1,
            );
        }
        //---1.2 更新关注用户信息
        if ($userC->isExistByOpenId($rawMsg['ToUserName'], $openId)) {
            $userC->updateByUserId($data);
        } else {
            $data['reg_time'] = date('Y-m-d h:i:s');
            $userC->add($data);
        }

        //2. 关注回复
        $responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        $subscribeReply = $responserC->replyForSubscribe($rawMsg);

        //3. 判断是否为扫码关注
        $scanReply = array();
        if (isset($rawMsg['Ticket']) && isset($rawMsg['EventKey'])) {
            $scanReply = $this->scan($rawMsg);
        }

        //4. 返回所有回复内容
        return array_merge(array($subscribeReply), $scanReply);
    }

    /**
     * 取消关注
     */
    public function unsubscribe($rawMsg)
    {
        $data = array(
            "user_id" => $rawMsg['FromUserName'],
            'last_time' => date('Y-m-d H:i:s'),
            "user_type" => 2,
        );
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $userM->updateByUserId($data);

        return array();
    }

    /**
     * 扫码关注
     */
    public function scan($rawMsg)
    {
        if ($rawMsg['ToUserName'] !=  Account::defaultWxId()) {
            return [];
        }

        //0. 解读二维码信息
        $qrCodeC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\QRCode');
        $qrSceneInfo = $qrCodeC->parseQrScene($rawMsg['EventKey']);
        if (empty($qrSceneInfo)) {
            return;
        }


        //1. 处理临时二维码和永久二维码
        $reply = array();
        if ($qrSceneInfo['type'] != 'QR_SCENE') {
            //__1.1 永久二维码
            $reply = $this->_indefiniteScan($rawMsg, $qrSceneInfo);
        } else {
            //__1.2 临时二维码
            $reply = $this->_tempScan($rawMsg, $qrSceneInfo);
        }

        //3. 扫码信息入库
        $this->scanLog($rawMsg, $qrSceneInfo);

        return $reply;
    }

    /**
     * @param $rawMsg
     * @param $qrSceneInfo
     * @return array
     */
    public function _getReplyMsg($rawMsg, $qrSceneInfo)
    {
        $openid = $rawMsg['FromUserName'];
        //获取二维码中stuId
        $stuid = $qrSceneInfo['data']['stuId'];
        //当前二维码场景为10时调用客服消息组件回复消息
        //获取 access_token
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken('default');
        // 客服消息组件
        $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
        $rawMsg['token'] = $token;
        $CustomerMesC->textMsg($rawMsg);
        $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');
        //记录扫码日志
        $this->scanLog($rawMsg, $qrSceneInfo);
        //处理自扫
        $userInfoC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\User');
        $userInfo = $userInfoC->getBindInfoById($openid, 1);
        if (!empty($userInfo) && ($userInfo['stu_id'] == $stuid)) {
            //自扫
            return [];
        }
        $scanM = \DevSxe\Lib\G('\DevSxe\Application\Model\BuyCardExchange\BuyCardExchange');
        $params['user_openid'] = $openid;
        $params['stuid_scanned'] = $stuid;
        $addRes = $scanM->addScanRecord($params);
        $scanInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\Op\SalesPromotion\wxcanStu', $openid);
        $param = ['stuid' => $stuid, 'openid' => $openid,'create_time' => time()];
        $data = array(
            $openid => $param
        );
        $res = Storage::hSet('\DevSxe\Application\Config\DataModel\Op\SalesPromotion\wxcanStu', $data);

        return [];
    }
    /**
     * 菜单点击事件
     */
    public function click($rawMsg)
    {
        if ($rawMsg['ToUserName'] !=  Account::defaultWxId()) {
            return [];
        }

        // 创建二维码
        if($rawMsg['EventKey']=='CLICK_CREATE_QRCODE'){
            fastcgi_finish_request();
            $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
            $token = $tokenMgr->getAccessToken('default');
            $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
            $openid =$rawMsg['FromUserName'];
            $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');

            // 已有二维码直接回复  
            $userInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid); 
            $now =  time();
            $creatTime = isset($userInfo['create_time'])?$userInfo['create_time']:'';
            $endTime = isset($userInfo['end_time'])?$userInfo['end_time']:'';
            if(!empty($userInfo) && ($now < $endTime) && ($now >$creatTime)){
                $nickname = $userInfo['nickname'];
                $media_id = $userInfo['media_id'];
                $CustomerMesC->sendCreatQRCode($token,$openid,$nickname);
                $CustomerMesC->sendPic($token,$openid,$media_id);
                // 记录用户日志
                unset($params);
                $params['openid'] = $openid;
                $params['action'] = 'click_create_QRcode';
                $params['reply'] = 'creatQRcode_alrealy_hava';
                $params['reply_user_openid'] = $openid ;
                $logM->addLog($params);
                return [];
            }else{
                // 用户昵称
                $nickname = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid)['nickname'];
                $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
                if(empty($nickname)){
                    $nickname = $CustomerMesC->getNickname($token,$openid)['nickname'];
                    $headimgurl = $CustomerMesC->getNickname($token,$openid)['headimgurl'];
                    unset($keysStr);
                    $keysStr[$openid] = ['nickname'=>$nickname,'headimgurl'=>$headimgurl];
                    Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);
                }

                $stuId = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid)['stu_id'];
                $tmpStuid = rand(1000000,9999999);
                if(empty($stuId)){
                    // $stuId = rand(1000000,9999999);
                    unset($keysStr);
                    $keysStr[$tmpStuid] = ['openid'=>$openid,'create_time'=>time()];
                    Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $keysStr);    
                }

                $stuId =!empty($stuId)?$stuId:$tmpStuid;
                $QRCodeC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\QRCode');
                $qrCodeUrl = $QRCodeC->getCode($stuId,7);

                // 下载对应链接二维码
                $tempMaterialC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\TempMaterial');
                $tempMaterialC->downLoad($qrCodeUrl,$openid,$status);
                $filePath = $tempMaterialC->combineImg($qrCodeUrl,$openid,$status);
                $data = ['media'=>new \CURLFile($filePath)];
                // 上传临时素材
                $mediaInfo = $tempMaterialC->upLoad($token,$data);
                $media_id = $mediaInfo['media_id'];
                $createTime = $mediaInfo['created_at'];
                $endTime = strtotime('+3 day',$createTime);
                unset($keysStr);
                $stu_id = !empty($stuId)?$stuId:$tmpStuid;
                $keysStr[$openid] = ['create_time'=>$createTime ,'end_time'=>$endTime,'media_id'=>$media_id,'stu_id'=>$stu_id,'is_used'=>1];
                Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);

                // 发送用户创建信息
                $CustomerMesC->sendCreatQRCode($token,$openid,$nickname);
                $CustomerMesC->sendPic($token,$openid,$media_id);
                // 记录用户日志
                unset($params);
                $params['openid'] = $openid;
                $params['action'] = 'click_create_QRcode';
                $params['reply'] = 'creatQRcode_first';
                $params['reply_user_openid'] = $openid ;
                $logM->addLog($params);
                return [];
            }
        }

        // 查询领取资格
        if($rawMsg['EventKey']=='CLICK_SEARCH_STATUS'){
            $openid = $rawMsg['FromUserName'];
            $userInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid);
            $stuId = $userInfo['stu_id'];

            //用户未参与活动时
            if(empty($stuId)){
                $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
                $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
                $token = $tokenMgr->getAccessToken('default');                
                $CustomerMesC->sendErr($openid,$token);    
            }else{
                $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
                $userDetail = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $stuId);
		$isFinish = isset($userDetail['is_finish'])?$userDetail['is_finish']:'0';

                $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
                $token = $tokenMgr->getAccessToken('default');
		if($isFinish==0){
		    $CustomerMesC->sendMisSta($token,$openid);
		    exit;
		}
		if($isFinish==1){
		    $CustomerMesC->sendFinish($token,$openid);
		    exit;	
		}
            }
        }

        if ($rawMsg['EventKey'] == 'CLICK_COURSE_SELECT') {

            $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
            $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
            $token = $tokenMgr->getAccessToken('default');
            $rawMsg['token'] = $token;
            $CustomerMesC->searchCoupons($rawMsg);
            exit;
        }
        if ($rawMsg['EventKey'] == 'CLICK_FIFTY_QRCODE') {
            //50元课老带新
            $this->fiftyCreateQrcode($rawMsg);
        }


        $responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        $respMenu = $responserC->replyForMenuEvent($rawMsg);
        return array($respMenu);
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
        if(!empty($userInfo) && ($now < $endTime) && ($now >$creatTime)){
            $media_id = $userInfo['media_id'];
            $msg = '主讲百里挑一，私教千人千面
预习有人讲，作业有人改
课堂互动多，好货可回放

语文、数学、英语， 50元10天双师直播课正在火热抢购中，
<a href="http://www.domain.com/devsxe.php?source=79412802&site_id=383&adsite_id=474403">点击立即抢购></a>

现在购买，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品，
如已购买50元课，直接分享下方卡片即可参与活动
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
            $CustomerMesC->eCardNoAccountMsg($token,$openid,$msg);
            $CustomerMesC->sendPic($token,$openid,$media_id);
            unset($params);
            $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');
            $params['openid'] = $openid;
            $params['action'] = 'click_Ecard_QRcode';
            $params['reply'] = 'creatQRcode_alrealy_hava';
            $params['reply_user_openid'] = $openid ;
            $logM->addLog($params);
            return [];
        }else{
            $QRCodeC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\QRCode');
            $qrCodeUrl = $QRCodeC->getCode($stuId,10);
            // 下载对应链接二维码
            $tempMaterialC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\TempMaterial');
            $tempMaterialC->downLoad($qrCodeUrl,$openid,1);
            $filePath = $tempMaterialC->combinePosterImg($qrCodeUrl,$openid,1);
            $data = ['media'=>new \CURLFile($filePath)];
            // 上传临时素材
            $mediaInfo = $tempMaterialC->upLoad($token,$data);
            $media_id = $mediaInfo['media_id'];
            $createTime = $mediaInfo['created_at'];
            $endTime = strtotime('+3 day',$createTime);
            unset($keysStr);
            $keysStr[$openid] = ['openid' => $openid,'stu_id' => $stuId,'create_time' => $createTime,'end_time' => $endTime,'media_id' => $media_id];
            Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);
            // 发送用户创建信息
            $msg = '主讲百里挑一，私教千人千面
预习有人讲，作业有人改
课堂互动多，好货可回放

语文、数学、英语， 50元10天双师直播课正在火热抢购中，
<a href="http://www.domain.com/devsxe.php?source=79412802&site_id=383&adsite_id=474403">点击立即抢购></a>

现在购买，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品，
如已购买50元课，直接分享下方卡片即可参与活动
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
            $CustomerMesC->eCardNoAccountMsg($token,$openid,$msg);
            $CustomerMesC->sendPic($token,$openid,$media_id);
            unset($params);
            $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');
            $params['openid'] = $openid;
            $params['action'] = 'click_Ecard_QRcode';
            $params['reply'] = 'creatQRcode_first';
            $params['reply_user_openid'] = $openid ;
            $logM->addLog($params);
            return [];
        }
    }
    /**
     * 永久二维码操作
     */
    private function _indefiniteScan($rawMsg, $qrSceneInfo)
    {
		//对特定永久二维码回复特定消息
		if($qrSceneInfo['data']['sceneId']==10032){
			fastcgi_finish_request();
			$tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
			$token = $tokenMgr->getAccessToken('default');
			$CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
			$openid = $rawMsg['FromUserName'];
			$CustomerMesC->sendScanMsg($token,$openid);
		}
        if($qrSceneInfo['data']['sceneId'] == 10034){
            fastcgi_finish_request();
            $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
            $token = $tokenMgr->getAccessToken('default');
            $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
            $rawMsg['token'] = $token;
            $CustomerMesC->searchCoupons($rawMsg);
        }
        	$responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        	$respScan = $responserC->replyForIndefiniteScan($rawMsg, $qrSceneInfo);
        	return array($respScan);
    }

    /*
     * 客服消息
     */ 
    private function _customerMes($rawMsg, $qrSceneInfo)
    { 
        //当前二维码场景为7时
        // 调用客服消息组件回复两条消息
        // access_token
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken('default');
        // 客服消息组件
        $CustomerMesC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\CustomerMessage');
        // openid
        $openid =$rawMsg['FromUserName'];

        // 用户昵称
        $nickname = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid)['nickname'];
        
        // 当缓存中无用户昵称时
        if(empty($nickname)){
            $nickname = $CustomerMesC->getNickname($token,$openid)['nickname'];
            $headimgurl = $CustomerMesC->getNickname($token,$openid)['headimgurl'];
            // 存入用户昵称 微信头像url
            unset($keysStr);
            $keysStr[$openid] = ['nickname'=>$nickname,'headimgurl'=>$headimgurl];
            Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);
        }
        
        // 发送一张图片
        // 首先生成二维码
        $info = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid);
        $createTime = $info['create_time'];
        $endTime = $info['end_time'];
        $now = time();
        // status 1.需要重新生成图片 2.不需要重新生成图片
        if($now>=$createTime && $now<=$endTime){
            $status = 2;
        }
        else{
            $status = 1;
        }
        $media_id =  Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid)['media_id'];

        // 1.首先查看现有缓存中是否有学生id
        $stuId = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid)['stu_id'];
        // 在生成二维码时存入创建二维码的人的微信号
        if(empty($media_id)||$status==1||empty($stuId)){
            
            // 为当前码中的stuid 和其对应的学生信息
            $parentStuid = $qrSceneInfo['data']['stuId'];
            $parentOpenid = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $parentStuid)['openid'];
            
            // 2.若现有缓存中没有学生id 查看原有缓存中是否有学生id
            if(empty($stuId)){
                $platformC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
                $stuId = $platformC->stuIdByPlatKey($rawMsg['FromUserName'], 8);
                if(!empty($stuId)){
                    // 查询其是否之前已扫他人二维码
                    $ownParentOpenid = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $stuId)['parents_id'];
                    if(empty($ownParentOpenid)){
                        unset($keysStr);
                        $keysStr[$stuId] = ['openid'=>$openid,'status'=>0,'create_time'=>time(),'parents_id'=>$parentOpenid];   
                        Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $keysStr);
                    }
                }
            }
            // 3.两次查询都无学生id 则随机生成一个学生id 
            if(empty($stuId)){
                // 使用当前时间戳 与随机数作为其学生id
                // $stuId =time() + rand(1,10000);
                // $stuId = (substr($stuId,3,-1))*rand(1,10);
                $stuId = rand(1000000,9999999);

                unset($keysStr);
                $keysStr[$stuId] = ['openid'=>$openid,'status'=>0,'create_time'=>time(),'parents_id'=>$parentOpenid];
                Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $keysStr);
            }

            $QRCodeC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\QRCode');
            $qrCodeUrl = $QRCodeC->getCode($stuId,7);

            // 下载对应链接二维码
            $tempMaterialC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\TempMaterial');
            $tempMaterialC->downLoad($qrCodeUrl,$openid,$status);

            // 下载用户头像
            /*$headimgurl = $info['headimgurl'];
            $tempMaterialC->downHeadImg($headimgurl,$openid,$status);*/
            // 将该二维码固定图片进行合成
            $filePath = $tempMaterialC->combineImg($qrCodeUrl,$openid,$status);
            $data = ['media'=>new \CURLFile($filePath)];
            // 上传临时素材
            $mediaInfo = $tempMaterialC->upLoad($token,$data);
            $media_id = $mediaInfo['media_id'];
            $createTime = $mediaInfo['created_at'];
            $endTime = strtotime('+3 day',$createTime);

            unset($keysStr);
            $keysStr[$openid] = ['create_time'=>$createTime ,'end_time'=>$endTime,'media_id'=>$media_id,'stu_id'=>$stuId];
            Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);

        }
        // 消息回复
        // 1.自扫
        // 2.他人扫（无二维码）
        // 3.他人扫（有二维码）
        // 二维码中stuid
        $qrStuid = $qrSceneInfo['data']['stuId'];
        // 扫码人的stuid
        $scanStuid = $stuId;
        // 1.自扫
        if($qrStuid == $scanStuid){
            $CustomerMesC->sendSelfScanMes($token,$openid,$nickname);
            $CustomerMesC->sendPic($token,$openid,$media_id);
            $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');
            //记录扫码日志    
            $this->scanLog($rawMsg, $qrSceneInfo);

            $params['openid'] = $openid;
            $params['action'] = 'self_scan';
            $params['reply'] = 'self_scan_msg';
            $params['reply_user_openid'] = $openid ;
            $logM->addLog($params);
            return [[]];
        }

        // 2.他人扫
        if($qrStuid != $scanStuid){
            // 2.他人扫（扫码人无二维码）
            $scanUserInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $openid);
            $isUsed = $scanUserInfo['is_used'];
            if($isUsed!=1){
                $CustomerMesC->sendMesage($token,$openid,$nickname);
                $CustomerMesC->sendPic($token,$openid,$media_id);


                //记录用户行为
                $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');                
                $params['openid'] = $openid;
                $params['action'] = 'scan_other';
                $params['reply'] = 'scan_other_msg';
                $params['reply_user_openid'] = $openid ;
                $logM->addLog($params);

                //3.记录扫码日志
                $this->scanLog($rawMsg, $qrSceneInfo);

                unset($keysStr);
                $keysStr[$openid] = ['is_used'=>1];
                Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\tmpImg', $keysStr);
                $qrInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $qrStuid);
                $qrOpenid = $qrInfo['openid'];
                $isFinish = $qrInfo['is_finish'];
                // 任务状态
                $missionStatus = $qrInfo['status'];
                // 当任务已完成时
                if($missionStatus==3){
                    $isFinish = 1;
                    unset($keysStr);
                    $keysStr[$qrStuid] = ['is_finish'=>1,];
                    Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $keysStr);
                    // 向任务发起人发送任务状态
                    $CustomerMesC->sendStatus($token,$qrOpenid,$nickname,$missionStatus);
                    

                    // 记录用户行为日志
                    unset($params);
                    $params['openid'] = $openid;
                    $params['action'] = 'scan_other';
                    $params['reply'] = 'send_to_other_hasbeen_scan';
                    $params['reply_user_openid'] = $qrOpenid;
                    $logM->addLog($params);
                    return [[]];
                }

                // 判断当前扫码人是否之前已扫
                $searchRes = array_search($openid, $qrInfo);
                if(!$searchRes){
                    if($missionStatus<3){
                        ++$missionStatus;                    
                        $offset = 'openid_'.$missionStatus;
                        unset($keysStr);
                        $keysStr[$qrStuid] = ['modify_time'=>time(),$offset=>$openid,'status'=>$missionStatus];
                        Storage::hSet('\DevSxe\Application\Config\DataModel\WeiXin\wx_tmpStu', $keysStr);
                        // 向任务发起人发送任务状态
                        $CustomerMesC->sendStatus($token,$qrOpenid,$nickname,$missionStatus);

                        // 记录用户行为日志
                        $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');                            
                        unset($params);
                        $params['openid'] = $openid;
                        $params['action'] = 'scan_other';
                        $params['reply'] = 'send_to_other_hasbeen_scan';
                        $params['reply_user_openid'] = $qrOpenid;
                        $logM->addLog($params);

                        return [[]];
                    }
                }
                return [[]];
            }
            // 3.他人扫(扫码人已有二维码)
            else{
                $CustomerMesC->sendShareMes($token,$openid,$nickname);
                $CustomerMesC->sendPic($token,$openid,$media_id);
                
                // 记录用户行为日志
                $logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLogs');                            
                unset($params);
                $params['openid'] = $openid;
                $params['action'] = 'scan_other';
                $params['reply'] = 'send_to_self_haveQRcode';
                $params['reply_user_openid'] = $openid;
                $logM->addLog($params);

                
                //3.记录扫码日志
                $this->scanLog($rawMsg, $qrSceneInfo);                
                return [[]];
            }
        }        
    }


    /**
     * 临时二维码操作
     */
    public function _tempScan($rawMsg, $qrSceneInfo)
    {

        // 对于场景7 的临时二维码特殊处理
        if($qrSceneInfo['data']['sceneId'] == 7){
	        fastcgi_finish_request();
            $this->_customerMes($rawMsg, $qrSceneInfo);
            exit;
        }

        // 对于场景10的临时二维码特殊处理
        if($qrSceneInfo['data']['sceneId'] == 10){
            fastcgi_finish_request();
            $this->_getReplyMsg($rawMsg, $qrSceneInfo);
            exit;
        }

        // 对于场景11的临时二维码特殊处理
        if($qrSceneInfo['data']['sceneId'] == 11){
            fastcgi_finish_request();
            $this->_customerMes($rawMsg, $qrSceneInfo);
            exit;
        }

        // 对于场景12的临时二维码特殊处理
        if($qrSceneInfo['data']['sceneId'] == 12){
            fastcgi_finish_request();
            \DevSxe\Lib\G('\DevSxe\Application\Component\Op\Invite\CubeCourse')->magicCubeSubscibeMsg($rawMsg, $qrSceneInfo);
            return [];
        }

        //0. 获取用户信息
        $userC = G('usersC');
        $userC->setParams(array('id' => $qrSceneInfo['data']['stuId']));
        $stuInfo = $userC->getUserInfoById();
        if ($stuInfo['stat'] == 0 || empty($stuInfo['data'])) {
            return array();
        }

        //1. 绑定检测

        $platformC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $stuId = $platformC->stuIdByPlatKey($rawMsg['FromUserName'], 8);
        if ($stuId == $stuInfo['data']['id']) {
            return array(
                array(
                    'type' => 'text',
                    'data' => '您已绑定',
                )
            );
        }
        $checkInfo = array(
            'stuId' => $stuInfo['data']['id'],
            'platKey' => $rawMsg['FromUserName'],
        );
        $bindStatusInfo = $platformC->checkBindStatus($checkInfo, 8);
        if ($bindStatusInfo['stat'] == 1) {
            return array(
                array(
                    'type' => 'text',
                    'data' => '抱歉，绑定失败，您的微信账号或开发测试账号已被绑定',
                )
            );
        }


        //2. 用户绑定
        $wxUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $bindResult = $wxUserC->bindWx($stuInfo['data']['id'], $rawMsg['FromUserName']);
        if ($bindResult['stat'] != 1) {
            return array();
        }

        //3. 充奖励金币
        $wxUserC->increaseGold($stuInfo['data']['id'], $stuInfo['data']['name']);

        //4. 回复（未关注扫码时，不进行绑定提示）
        $responserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Responser');
        $respScan = $responserC->replyForTempScan($rawMsg, $qrSceneInfo);
        return array($respScan);
    }

    /**
     * 扫码保存日志
     * @param type $rawMsg 扫码信息
     * @param type $qrSceneInfo 二维码场景信息
     * @param type $bindStatus  绑定状态
     */
    public function scanLog($rawMsg, $qrSceneInfo)
    {
        $type = 1;
        $stuId = 0;
        $bindStatus = 0;
        $sceneId = $qrSceneInfo['data']['sceneId'];
        if ($qrSceneInfo['type'] == 'QR_SCENE') {
            $type = 2;
            $stuId = $qrSceneInfo['data']['stuId'];
            $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
            if ($userC->isBindWx($rawMsg['FromUserName'])) {
                $bindStatus = 1;
            }
        }

        $scanData = array(
            'stu_id' => $stuId,
            'scene_id' => $sceneId,
            'type' => $type,
            'bind' => $bindStatus,
            'open_id' => $rawMsg['FromUserName'],
            'ticket_id' => $rawMsg['Ticket'],
            'Event' => $rawMsg['Event'],
            'EventKey' => $rawMsg['EventKey'],
            'msg_main_id' => $this->getMsgLogId(),
            'scan_time' => date('Y-m-d H:i:s'),
        );
        $qrCodeM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\QRCode');
        $qrCodeM->addScanLog($scanData);
    }

}
