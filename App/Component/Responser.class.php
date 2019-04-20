<?php

/**
 * 消息响应
 */

namespace DevSxe\Application\Component\Wx;
use DevSxe\Lib\Storage;

class Responser
{

    /**
     * 回复文本消息
     * @param type $rawMsg 原始消息
     */
    public function replyForText($rawMsg)
    {
        $content = $rawMsg['Content'];
        $replyC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Reply');

        if ($rawMsg['ToUserName'] !=  Account::defaultWxId()) {
            $rawKw= $content;
            // 根据关键字获取
            $rawRespInfo = $replyC->getByKW($rawMsg['ToUserName'], $content, 2);
            if (empty($rawRespInfo)) {
                return [];
            }

            $maxCount = 180;
            $curKwCount = $replyC->kwRespNumber($rawMsg['ToUserName'], $content);
			$lastRespInfo = $rawRespInfo;
            $index = 1;
            while($lastRespInfo['type'] == 3 && $curKwCount+1 > $maxCount) {
                $content = $rawKw . '-' . $index;
                $curRespInfo = $replyC->getByKW($rawMsg['ToUserName'], $content, 2);
                if (empty($curRespInfo)) {
                    break;
                }
                $index++;
				$lastRespInfo = $curRespInfo;
                $curKwCount = $replyC->kwRespNumber($rawMsg['ToUserName'], $content);
            }
            $respMsg = $this->_replyByAutoReply($lastRespInfo);
            if (empty($respMsg)) {
                return [];
            }
            $replyC->incrRespNumber($rawMsg['ToUserName'], $lastRespInfo['content']);
            return $respMsg;
        }

        $match_content = '';
        $regularExpressionList = array();
        // 根据关闭开启状态查找回复关键词 1,关闭；2，开启
        $results = $replyC->getRepliesByStatus($rawMsg['ToUserName'], 2);
        foreach ($results as $value) {
            $regularExpressionList[] = $value['content'];
        }

        if (!empty($regularExpressionList)) {
            if (in_array($content, $regularExpressionList)) {
                $match_content = $content;
            } else {
                $regularExpression = implode("|", $regularExpressionList);
                $regularExpression = "/" . $regularExpression . "/";
                if (preg_match($regularExpression, $content, $matches)) {
                    $match_content = $matches[0];
                    //暂时是替换"+"为"\+"
                    $match_content = str_replace("+", "\+", $match_content);
                }
            }
        }

        // 根据关键字获取
        $respInfo = $replyC->getByKW($rawMsg['ToUserName'], $match_content, 2);
        if (empty($respInfo)) {
            return [
                'type' => 'text',
                'data' => '夸奖开发测试请直说，咨询问题请拨打4008002211',
            ];
        }
        $resMsg = $this->_replyByAutoReply($respInfo);
        return $resMsg;
    }

    /**
     * 语音消息回复
     */
    public function replyForVoice($rawMsg)
    {
        $kfAccount = 'kf2032@domainwangxiao';
        $kfData = $this->_getOnlineKf();
        if (!empty($kfData)) {
            $kfAccount = $kfData[array_rand($kfData, 1)];
        }
        return arraY(
            'type' => 'customService',
            'data' => $kfAccount,
        );
    }

    //***************事件****************
    /**
     * 获取回复的消息
     * @param array $rawMsg 原始事件信息
     * @return array 回复内容
     *     return array(
     *          'type' => 'text',   回复消息类型
     *          'data' => $content, 回复消息内容
     *      );
     */
    public function replyForSubscribe($rawMsg)
    {
        if ($rawMsg['ToUserName'] !=  Account::defaultWxId()) {
            $replyC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Reply');
            // 根据关键字获取
            $respInfo = $replyC->getByKW($rawMsg['ToUserName'], '关注提示语', 2);
            if (empty($respInfo)) {
                $defaultSubsribe = <<<replyInfo
欢迎来到“21天单词训练营”，由哈哈开发测试倾力打造的专业外教+情景式单词记忆学习模式，开班记单词啦！\n
[21天单词训练营]：每天五分钟，限时限额，每晚八点，不见不散！\n
欢迎加入我们，请回复“1”，弹出二维码，即可扫码加群。
replyInfo;
                return [
                    'type' => 'text',
                    'data' => $defaultSubsribe,
                ];
            }
            $resMsg = $this->_replyByAutoReply($respInfo);
            return $resMsg;
        }

        $tipsC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Tips');
        $tips = $tipsC->getValidByCategory(1);
        if (empty($tips)) {
            $defaultSubsribe = <<<replyInfo
Hello！我是最爱你的开发测试“宝哥哥”，我已经等你很久了。我有一种超级能力，你知道是什么嘛？那就是可以让学习.变的有意思，让服务变的更方便，不信你喊宝哥哥试试？\xf0\x9f\x8c\xb9
对了，对了，还有更实时的热点政策、最新优惠、以及精彩活动不断推送，神马？你刚刚关注?请点击右上角,然后“查看历史消息”了解往期活动吧！\xf0\x9f\x8e\x81
别问我是谁，我是宝哥哥！\xf0\x9f\x98\x83
replyInfo;
            return array(
                'type' => 'text',
                'data' => $defaultSubsribe,
            );
        }
        return $this->_replyByTip($tips[0]);
    }

    /**
     * 临时二维扫码回复
     * @param array $rawMsg 原始事件信息
     * @param array $qrSceneInfo 二维码信息
     * @return array 回复内容
     */
    public function replyForTempScan($rawMsg, $qrSceneInfo)
    {
        if ($rawMsg['Event'] == 'subscribe') {
            return;
        }
        return array(
            'type' => 'text',
            'data' => '我们已为您绑定开发测试账号，您可以享受及时的直播提醒、账户查询等服务。',
        );
    }

    /**
     * 永久二维码扫码回复
     * @param array $rawMsg 原始事件信息
     * @param array $qrSceneInfo 二维码信息
     * @return array 回复内容
     */
    public function replyForIndefiniteScan($rawMsg, $qrSceneInfo)
    {
        return array();
    }


    /**
     * 菜单回复
     */
    public function replyForMenuEvent($rawMsg)
    {
        switch ($rawMsg['EventKey']) {
            case 'CLICK_LOGISTICS_NUM': //物流单号
                return $this->_getScore($rawMsg['FromUserName']);
            case 'CLICK_SCORE_SHOW':   //成绩
                return $this->_autoService($rawMsg['FromUserName']);
            default:
                if ('CLICK_CUSTOM_' != substr($rawMsg['EventKey'], 0, 13)) {
                    return array();
                }
                $tipsC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Tips');
                $tipId = substr($rawMsg['EventKey'], 13);
                $tipInfo = $tipsC->getValid($tipId, 4);
                return $this->_replyByTip($tipInfo);
        }
    }

    /**
     * 根据回复提示生成回复内容和类型
     * @param array $tip
     * @return array array( 'type' => 类型, 'data' => 内容);
     */
    private function _replyByTip($tip)
    {
        if (empty($tip) || empty($tip['content'])) {
            return array();
        }

        $content = $tip['content'];
        //文本消息
        if ($tip['type'] == 1) {
            return array(
                'type' => 'text',
                'data' => $content,
            );
        }

        //图文消息
        $materialC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Material');
        $date = $materialC->preContent($content);
        return array(
            'type' => 'news',
            'data' => $date['articles'],
        );
    }

    /**
     * 根据自动回复信息生成回复内容和类型
     * @param array $reply
     * @return array array( 'type' => 类型, 'data' => 内容);
     */
    private function _replyByAutoReply($reply)
    {
        if (empty($reply['reply_text']) && empty($reply['reply_ids'])) {
            return [];
        }
        switch ($reply['type']) {
            case 1:
                return ['type' => 'text', 'data' => $reply['reply_text']];
            case 2:
                $materialC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Material');
                $date = $materialC->preContent($reply['reply_ids']);
                return ['type' => 'news', 'data' => $date['articles']];
            case 3:
                return ['type' => 'image', 'data' => $reply['reply_ids']];
            default:
                return [];
        }
    }

    /**
     * 获取用户物流信息
     */
    private function _getExpress($openId)
    {
        $tipsC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Tips');
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $isbind = $userC->isBindWx($openId);

        //1. 未绑定
        if ($isbind == false) {
            $tipInfo = $tipsC->getValid(1, 5);
            return $this->_replyByTip($tipInfo);
        }


        //2. 已绑定
        $expresses = $userC->getExpressByOpenId($openId, 0);
        if (empty($expresses)) {
            $tipInfo = $tipsC->getValid(19, 5);
            $reply = $this->_replyByTip($tipInfo);
            if (empty($reply)) {
                return array(
                    'type' => 'text',
                    'data' => '抱歉未能查到您的订单信息！',
                );
            }
            return $reply;
        }

        //组装用户物理信息
        $totalInfo = '';
        foreach ($expresses as $val) {
            $express = array(
                '名称：' . $val['content'],
                '物流公司：' . $val['express'],
                '单号：' . $val['express_sn'],
                '状态：已发货',
            );
            $totalInfo .= implode("\n", $express);
            $totalInfo .= "\n\n";
        }
        return array(
            'type' => 'text',
            'data' => trim($totalInfo, "\n\n"),
        );
    }

    private function _autoService($openId)
    {
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $stuId = $userC->getStuIdByOpenId($openId);
        if (empty($stuId)) {
            return ['type' => 'text', 'data' => '<a href="https://login.domain.com/">您还未绑定开发测试账号</a>'];
        }

        ////$asC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AutoService');
        ////$resp = $asC->respToUser($stuId);

        //活动
        //1. 是否参数活动
        $cacheIds = implode('_', [4, 5, 6, 7]);
        $conf = '\DevSxe\Application\Config\DataModel\StuActivity\activityReachNum';

        //1. 达成数
        $curScore = Storage::zScore($conf, $cacheIds, $stuId);
        if($curScore === false) {
            return ['type' => 'text', 'data' => '您尚未参加活动，请先购买1元课后参与邀请好友活动。'];
        }
        
        //2. 总参与人数
        $total = Storage::zCount($conf, $cacheIds, -1, 100000);

        //3. 排名
        if ($curScore == 0) {
            $maxIndex = Storage::zCount($conf, $cacheIds, 1, 100000);
            $curIndex = $maxIndex + 1; //当前排名
        } else {
            $offset = Storage::zCount($conf, $cacheIds, $curScore, $curScore);
            $maxIndex = Storage::zCount($conf, $cacheIds, $curScore, 100000);
            $curIndex = $maxIndex - $offset + 1; //当前排名
        }
        $respText = '您在1元课邀请好友活动中，共邀请了'
            . $curScore . '位好友，目前共有' . $total
            . '位学员参与活动，您的排名：第' .$curIndex. '位'
            . '，前1000名将获得价值588元的kindle电纸书一台！';
        return ['type' => 'text', 'data' => $respText];
    }

    /**
     * 获取用户考试成绩
     */
        private function _getScore($openId)
        {
            $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
            $stuId = $userC->getStuIdByOpenId($openId);
            if (empty($stuId)) {
                return ['type' => 'text', 'data' => '<a href="https://login.domain.com/">您还未绑定开发测试账号</a>'];
            }

            $asC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AutoService');
            $resp = $asC->respToUser($stuId);
            return ['type' => 'text', 'data' => $resp];
    }

    /**
     * 获取在线客服信息
     * @return type
     */
    private function _getOnlineKf()
    {
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken(Account::DEFAULT_WX_ID_SIGN);

        $url = "https://api.weixin.qq.com/cgi-bin/customservice/getonlinekflist?access_token=" . $token;
        $result = file_get_contents($url);

        $kfOnline = json_decode($result, true);
        if (empty($kfOnline['kf_online_list'])) {
            return array();
        }

        $kfData = array();
        foreach ($kfOnline['kf_online_list'] as $val) {
            $kfData[] = $val['kf_account']; //随机客服
        }
        return $kfData;
    }

}
