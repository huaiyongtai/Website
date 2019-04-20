<?php

/*
 * 微信用户信息对外接口
 *
 */

namespace DevSxe\Application\Controller\WeiXin;

use \DevSxe\Application\Controller\AppController;

/**
 * 微信用户信息
 */
class UserInfo extends AppController
{

    /**
     * 获取用户的授权信息
     * @param  string $code   授权code票据
     * @param  int    $type   授权类型
     * @param  int    $source 业务来源
     * @return array  用户授权信息
     */
    public function auth()
    {
        $params = $this->params;
        if (empty($params['code']) || empty($params['type'])) {
            return ['stat' => 0, 'data' => '参数不完整'];
        }
        $webOAuthC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\WebOAuth');
        $result = $webOAuthC->auth($params['code'], $params['type'], $params['source']);
        if (empty($result)) {
            return ['stat' => 0, 'data' => '授权失败'];
        }
        return ['stat' => 1, 'data' => $result];
    }

    /**
     * 获取微信用户信值
     */
    public function uInfo()
    {

        if (empty($this->params['openId'])) {
            return ['stat' => 0, 'data' => '缺少参数OpenId'];
        }
        $openId = $this->params['openId'];

        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $uInfo = $userC->getFromWxByOpenId($openId);
        if (empty($uInfo)) {
            return ['stat' => 0, 'data' => '信息获取失败'];
        }
        return ['stat' => 1, 'data' => $uInfo];
    }

    /**
     * 获取用户微信openid值
     * @param string $code 网页认证之后得到的code票据
     * @return array 用户微信openid值
     */
    public function getUserOpenId()
    {
        $tokenMgrC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $result = $tokenMgrC->getOAuthAccessToken($this->params['code']);
        if (empty($result)) {
            return array('stat' => 0, 'rows' => 0, 'data' => array());
        }
        return array('stat' => 1, 'rows' => 1, 'data' => $result['openid']);
    }

    /**
     * 获取用户微信绑定信息
     * @param string $openId 用户微信openId
     * @return type Description
     */
    public function weixinDevSxeBindGet()
    {
        $userInfoC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\User');
        $userInfo = $userInfoC->weixinDevSxeBindGet($this->params['devsxe_openid']);

        if (empty($userInfo)) {
            return ['stat' => 0, 'data' => '未查询到该用户的绑定信息'];
        }
        return ['stat' => 1, 'data' => $userInfo];
    }


}
