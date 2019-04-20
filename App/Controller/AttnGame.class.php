<?php

/**
 * 专注力游戏
 */
namespace DevSxe\Application\Controller\WeiXin;

use \DevSxe\Application\Controller\AppController;

class AttnGame extends AppController
{

    /**
     * 添加游戏纪录
     */
    public function addLog()
    {
        $params = $this->params;

        //1. 添加游戏
        $logInfo['time'] = $params['time'];
        $logInfo['openid'] = $params['openid'];
        $logInfo['chapter_id'] = $params['chapter_id'];
        $attnGameC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AttnGame');
        $attnGameC->addLog($logInfo);

        //2. 获取本场游戏最佳成绩
        $minTime = $attnGameC->minTime($logInfo['openid'], $logInfo['chapter_id']);
        if (empty($minTime)) {
            $minTime = $params['time'];
        }
        return [
            'stat' => 1,
            'data' => [
                'minTime' => $minTime,
                'curTime' => $params['time'],
                'chapter_id' => $params['chapter_id'],
            ],
        ];
    }

    /**
     * 游戏参加人数
     */
    public function joinTotal()
    {
        $attnGameC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AttnGame');
        $joinTotal = $attnGameC->joinTotal();
        return [
            'stat' => 1,
            'data' => ['joinTotal' => $joinTotal],
        ];
    } 

    /**
     * 用户排名
     */
    public function userRank()
    {
        //当前排名信息
        $openId = $this->params['openid'];
        $attnGameC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AttnGame');
        $userRank = $attnGameC->userRank($openId);
        if (empty($userRank)) {
            return ['stat' => 0, 'data' => '您未完成该游戏，无法查询'];
        }
        return [
            'stat' => 1,
            'data' => $userRank,
        ];
    }

    /**
     * 排名列表
     */
    public function rankList()
    {
        $openId = $this->params['openid'];
        $attnGameC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\AttnGame');
        $userRanks = $attnGameC->rankList($openId);
        return [
            'stat' => 1,
            'data' => ['list' => $userRanks]
        ];
    }
}
