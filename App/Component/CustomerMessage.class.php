<?php
   
/**
 * 微信客服消息
 */
namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Curl;

class CustomerMessage
{
    /**
     * 客服消息回复
     */
    public function eCardNoAccountMsg($token, $openid, $msg)
    {
       $Msg = [
           'touser' => $openid,
            'msgtype' => 'text',
            'text' => ['content'=>$msg],
        ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }
    //扫E卡二维码回复消息
    public function textMsg($rawMsg)
    {
        $stuId = !empty($rawMsg['userid']) ? $rawMsg['userid'] : 0;
        $coupons = !empty($rawMsg['coupons']) ? json_decode($rawMsg['coupons'], true) : array();
        $tmpMsg = '主讲百里挑一，私教千人千面
预习有人讲，作业有人改
课堂互动多，好货可回放

语文、数学、英语， 50元10天双师直播课正在火热抢购中，
<a href="http://www.domain.com/devsxe.php?source=79412802&site_id=383&adsite_id=474403">点击立即抢购></a>

现在购买，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品，
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
        if (!empty($stuId) && !empty($coupons) && is_array($coupons)) {
            $tmpMsg = '恭喜您抢购哈哈开发测试50元好货成功！
分享下方“学习邀请卡”到朋友圈或微信群，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
        }
        $Msg = [
            'touser' => $rawMsg['FromUserName'],
            'msgtype' => 'text',
            'text' => ['content'=>$tmpMsg],
        ];
        $option = array('getUrl' => ('access_token=' . $rawMsg['token']));
        $result = Curl::post(3002, $Msg, $option);
    }
    /**
     * 查询用户E卡
     */
    public function searchCoupons($rawMsg)
    {
        $stuId = !empty($rawMsg['userid']) ? $rawMsg['userid'] : 0;
        $coupons = !empty($rawMsg['coupons']) ? json_decode($rawMsg['coupons'], true) : array();
        $tmpMsg = '您还没有可选的50元双师直播课哦！

主讲百里挑一，私教千人千面
预习有人讲，作业有人改
课堂互动多，好货可回放

语文、数学、英语， 50元10天双师直播课正在火热抢购中，
<a href="http://www.domain.com/devsxe.php?source=79412802&site_id=383&adsite_id=474403">点击立即抢购></a>

现在购买，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品，
如已购买50元课，直接分享下方卡片即可参与活动
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
        if (empty($stuId)) {
            $tmpMsg = '恭喜您抢购哈哈开发测试50元好货成功！
先绑定开发测试账户才可以选课哦！
<a href="https://login.domain.com/">立即绑定</a>
温馨提示：初始密码是您手机号的后6位~
如有问题，点击<a href ="https://www.sobot.com/chat/pc/index.html?sysNum=4a1c52959a974dd497260f4e0d50f1c9">咨询在线客服</a>
或拨打开发测试24小时客服热线 400-800-2211
↓↓↓绑定成功后请点击下方菜单栏-50元课-立即选课 选定上课时间↓↓↓';
        } elseif (!empty($coupons)) {
            if (is_array($coupons)) {
                $tmpMsg = "热门时段好货名额有限，请尽快选课哦！
 ";
                foreach ($coupons as $cardId => $cardName) {
                    $url  = "http://touch.domain.com/courseSelect/" . $cardId;
                    $tmpMsg .= "<a href='" . $url . "'>" . $cardName . " 选课中心></a>
";
                }
                $tmpMsg .= '分享下方“学习邀请卡”到朋友圈或微信群，还有机会领取iPad Pro 、戴森吸尘器、家用投影仪等千元礼品
<a href ="http://zt.domain.com/2018gkj-bd/">点击查看活动详情></a>';
            }
        }
        $Msg = [
            'touser' => $rawMsg['FromUserName'],
            'msgtype' => 'text',
            'text' => ['content'=>$tmpMsg],
        ];
        $option = array('getUrl' => ('access_token=' . $rawMsg['token']));
        $result = Curl::post(3002, $Msg, $option);
    }

    // 正常扫码回复普通文本消息
    public function sendMesage($token,$openid,$nickname)
    {

        $discountUrl = 'http://www.domain.com/devsxe.php?source=39602125&site_id=381&adsite_id=309948';
        $discount = "<a href = '$discountUrl'>戳此购买好货</a>";

	$kefuUrl = 'https://www.sobot.com/chat/pc/index.html?sysNum=4a1c52959a974dd497260f4e0d50f1c9';
	$kefu = "<a href = '$kefuUrl'>点此联系客服</a>";

	$courseUrl = 'http://zt.domain.com/zaixian/50zty/index.html';
	$course = "<a href = '$courseUrl'>《哈哈英语寒假长期班》</a>";

        $courseIntroduce = 'http://zt.domain.com/2017wjxkyy/';
        $courseIntroduceUrl = "<a href = '$courseIntroduce'>《外教学科英语小课堂》</a>";
	    $tmpMsg = $nickname.'，送您一份见面礼'.$this->unicode2utf8_2('\ud83c\udf81').':
»原价500元的'.$course.'限时50元，

»点击上方链接或下方“点此联系客服”了解好货内容，

'
.$this->unicode2utf8_2('\ud83d\udc49').$discount.'

【福利】

将下方卡片发送至朋友圈或好友，邀请好友一起学习《哈哈英语寒假长期班》：

'.$this->unicode2utf8_2('\ud83d\udc49').'1人扫码并立即购买，即可免费获得

价值98元的'.$courseIntroduceUrl.$this->unicode2utf8_2('\ud83d\udc49').$kefu.'客服电话：4008002211';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    // 回复自扫消息
    public function sendSelfScanMes($token,$openid,$nickname)
    {
		$discountUrl = 'http://www.domain.com/devsxe.php?source=39602125&site_id=381&adsite_id=309948';
		$discount = "<a href = '$discountUrl'>戳此立即购买</a>";
		$tmpMsg = '快把你的专属邀请卡保存至手机相册，邀请好友扫码吧！
'.$discount.'50元超值体验课';
        $Msg = [ 'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }


    //  已有二维码时,通知让其分享
    public function sendShareMes($token,$openid,$nickname)
    {
		$discountUrl = 'http://www.domain.com/devsxe.php?source=39602125&site_id=381&adsite_id=309948';
		$discount = "<a href = '$discountUrl'>戳此立即购买</a>";
		$tmpMsg = '你已拥有二维码，不能再支持其他人啦
'.$discount.
'
快邀请更多人参与本活动吧！';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }


    // 回复图片消息
    public function sendPic($token,$openid,$media_id)
    {
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'image',
                    'image' => ['media_id'=>$media_id],
        ];
        $option = array('getUrl' => ('access_token=' . $token));
        return Curl::post(3002, $Msg, $option);
    }


    // 回复任务状态
    public function sendStatus($token,$openid,$nickname,$status)
    {
	    if($status==1){
    		$tmpMsg = '你的好友'.$nickname.'帮你扫码啦！TA一直默默得关注着你~要有1人购买和你一起学习，你就可以免费获得价值98元的《外教学科英语小课堂》';
    	}
    	if($status==2){
    		$tmpMsg = '你的好友'.$nickname.'帮你扫码啦！你肯定是'.$nickname.'的真爱！~要有1人购买和你一起学习，就可以免费获得98元的《外教学科英语小课堂》';
    	}
    	if($status==3){
    		$tmpMsg = $nickname.'帮你扫码啦！一定要请TA吃饭哦！~要有1人购买和你一起学习，就可以免费获得98元的《外教学科英语小课堂》';
    	}
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    // 发送领取20元优惠券链接
    // 现改为领取好货链接
    public function RewardsMsg($token,$openid,$nickname)
    {
        $discountUrl = 'http://weixin.domain.com/WxDiscount/courseReceiveShow?code='.$openid;
        $discount = "<a href = '$discountUrl'>戳此领取 </a>";
        $tmpMsg = '恭喜！你的好友'.$nickname.'通过扫描你的二维码购买《哈哈英语寒假长期班》课啦~！今后可以一起交流学习经验哦，大家一起努力，让孩子听得多，考得好！
~你也免费获得了

《外教学科英语小课堂》

赠课领取后在哈哈开发测试APP学习中心查看

'.$discount;
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    // 查询用户任务状态
    public function sendUserStatus($token,$openid,$status)
    {
        $discountUrl = 'http://weixin.domain.com/WxDiscount/courseReceiveShow?code='.$openid;
        $discount = "<a href='$discountUrl'>领课链接</a>";
        if($status<3){
            //剩余人数
            $Num = 3-$status;
            $tmpMsg = '您当前已邀请了'.$status.'名好友只要再邀请'.$Num.'名好友就能完成任务啦';
        }else{
            $tmpMsg = '您已经成功完成了任务快去领取好货吧 '.$discount;
        }
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }



    public function unicode2utf8_2($str){	//关于unicode编码转化的第二个函数，用于显示emoji表情
        $str = '{"result_str":"'.$str.'"}';	//组合成json格式
	$strarray = json_decode($str,true);	//json转换为数组，利用 JSON 对 \uXXXX 的支持来把转义符恢复为 Unicode 字符（by 梁海）
	return $strarray['result_str'];
    }

    // 发送创建二维码消息
    public function sendCreatQRCode($token,$openid,$nickname)
    {
        $discountUrl = 'http://www.domain.com/devsxe.php?source=39602125&site_id=381&adsite_id=309948';
        $discount = "<a href = '$discountUrl'>戳此购买好货</a>";

	$kefuUrl = 'https://www.sobot.com/chat/pc/index.html?sysNum=4a1c52959a974dd497260f4e0d50f1c9';
        $kefu = "<a href = '$kefuUrl'>点此联系客服</a>";

	$courseUrl = 'http://zt.domain.com/zaixian/50zty/index.html';
	$course = "<a href = '$courseUrl'>《哈哈英语寒假长期班》</a>";

        $courseIntroduce = 'http://zt.domain.com/2017wjxkyy/';
        $courseIntroduceUrl = "<a href = '$courseIntroduce'>《外教学科英语小课堂》</a>";
        $tmpMsg = $nickname.'，送您一份见面礼'.$this->unicode2utf8_2('\ud83c\udf81').'：
»原价500元的'.$course.'限时50元，

»点击上方链接或下方“点此联系客服”了解好货内容，

'
.$this->unicode2utf8_2('\ud83d\udc49').$discount.'

【福利】

将下方卡片发送至朋友圈或好友，邀请好友一起学习《哈哈英语寒假长期班》：

'.$this->unicode2utf8_2('\ud83d\udc49').'1人扫码并立即购买，即可免费获得

价值98元的'.$courseIntroduceUrl.'

'.$this->unicode2utf8_2('\ud83d\udc49').$kefu.'客服电话：4008002211';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }


    /*
     *获取用户信息
     *$token  微信access_token
     *$openid 用户openid
     *return array (返还用户微信信息 昵称 头像)
     */
    public function getNickname($token,$openid)
    {
        $json = Curl::get('3011', array('getUrl' => "access_token={$token}&openid={$openid}"))['data'];
        $result = json_decode($json,true);
        return $result;
    }

    public function sendErr($token,$openid)
    {
        $tmpMsg = '您当前还未创建二维码，快点击按钮创建你的二维码吧';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }



    //提示用户还未完成任务
    public function sendMisSta($token,$openid){
        $tmpMsg = '您的好友当前还没有买课，不能领取奖励哦';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    //用户完成任务发送领课链接
    public function sendFinish($token,$openid){
	    $discountUrl = 'http://weixin.domain.com/WxDiscount/courseReceiveShow?code='.$openid;
        $discount = "<a href = '$discountUrl'>戳此领取 </a>";
        $tmpMsg = '恭喜您已完成了任务快去领课吧'.$discount.'

领取到的好货可以在哈哈开发测试app的学习中心查看';
        $Msg = [
                    'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    // 回复查看二维码信息
    public function sendQrCode($token,$openid,$qrCodeUrl)
    {
        $discount = "<a href = '$qrCodeUrl'>我的二维码 </a>";
        $tmpMsg = $discount;
        $Msg = [ 'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }
    
    // 当用户未创建二维码时提示用户先创建二维码 
    public function sendNotice($token,$openid)
    {
        $tmpMsg = '您当前尚未拥有二维码，请先回复关键字【50元课】创建你的专属二维码';
        $Msg = [ 'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    
    //扫描永久二维码
    public function sendScanMsg($token,$openid)
    {
	$discountUrl = 'http://www.domain.com/devsxe.php?source=39602125&site_id=381&adsite_id=309948';
	$discount = "<a href = '$discountUrl'>戳此购买好货</a>";

	$kefuUrl = 'https://www.sobot.com/chat/pc/index.html?sysNum=4a1c52959a974dd497260f4e0d50f1c9';
	$kefu = "<a href = '$kefuUrl'>点此联系客服</a>";

	$tmpMsg = '欢迎来到哈哈开发测试！在线学习更有效！

近期特价好货：
»原价500元的《哈哈英语寒假长期班》限时50元'.$this->unicode2utf8_2('\ud83d\udc49').$discount.'

'.$this->unicode2utf8_2('\ud83d\udc49').'免费领取更多好课：
点击下方菜单【50元课】参加领课活动。

'.$this->unicode2utf8_2('\ud83d\udc49').$kefu.'客服电话：4008002211';
        $Msg = [ 'touser' => $openid,
                    'msgtype' => 'text',
                    'text' => ['content'=>$tmpMsg],
                ];
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3002, $Msg, $option);
    }

    public function magicCubeMsg($token, $openid)
    {
        // 文本消息
        $weChatBindToolUrl = "http://activity.domain.com/topic/growth/wxloginstatus";
        $content = <<<DEVSXE
第❶步
您已成功购买“魔方课”第1节！邀请2位好友成功报名即可解锁魔方课剩余全部好货（共9节课）

第❷步
请保存您的“专属邀请海报”到您的手机相册，发到微信群、朋友圈，好友扫码成功报名后即可解锁魔方课剩余全部好货（海报见下方图片↓↓↓）

第❸步
重要提醒！！！请使用您的微信立即<a href="{$weChatBindToolUrl}">点此绑定哈哈开发测试账号</a>，绑定后您可以回复相应数字：
数字【1】：查询成功邀请到的好友人数；
数字【2】: 查询上课方式；
数字【3】: 获取专属邀请海报；

我们在开发测试等你哦！
DEVSXE;

        $Msg = [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ],
        ];
        $option = array('getUrl' => ('access_token=' . $token));
        return Curl::post(3002, $Msg, $option);
    }
}


