<?php

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Curl;
use \DevSxe\Service\Log\DevSxeLog;

class WebOAuth
{
    /**
     * 授权
     *
     * @param  $code
     * @param  $type
     * @param  $source
     */
    public function auth($code, $type = 1, $source = 1)
    {
        //1. 验证
        $tokenInfo =  $this->_authBase($code);
        if (empty($tokenInfo)) {
            return array();
        }
        $authInfo = ['openid' => $tokenInfo['openid']];

        //2. 非静默方式获取用户详细信息
        if ($type == 2) {
            $userInfo = $this->_authUserInfo($tokenInfo['access_token'], $tokenInfo['openid']);
            if (empty($userInfo) || empty($userInfo['openid'])) {
                return $authInfo;
            }
            $authInfo['sex'] = $userInfo['sex'];
            $authInfo['city'] = $userInfo['city'];
            $authInfo['openid'] = $userInfo['openid'];
            $authInfo['country'] = $userInfo['country'];
            $authInfo['unionid'] = isset($userInfo['unionid']) ? $userInfo['unionid'] : '';
            $authInfo['nickname'] = $userInfo['nickname'];
            $authInfo['province'] = $userInfo['province'];
            $authInfo['privilege'] = implode('|', $userInfo['privilege']);
            $authInfo['headimgurl'] = $userInfo['headimgurl'];

            //3. 记录授权日志
            $authLog = array_merge($authInfo, ['type' => $type, 'source' => $source]);
            $webOAuthM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\WebOAuth');
            $webOAuthM->addAuthLog($authLog);
        }
        return $authInfo;
    }

    /**
     * 获取用户授权信息
     */
    public function userInfos($openIds, $type = 1, $source = 1)
    {
        $webOAuthM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\WebOAuth');
        return $webOAuthM->userInfos($openIds, $source, $type);
    }

    /**
     * 获取授权票据
     */
    private function _authBase($code)
    {
        $accountInfoC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\Account');
        $wxId = $accountInfoC->defaultWxId();
        $accountInfo = $accountInfoC->authInfo($wxId);
        if (empty($accountInfo)) {
            return array();
        }

        $urlStr = 'code=' . $code;
        $urlStr .= '&appid=' . $accountInfo['APP_ID'];
        $urlStr .= '&secret=' . $accountInfo['APP_SERCET'];
        $urlStr .= '&grant_type=authorization_code';
        $result = Curl::get(3008, ['getUrl' => $urlStr]);
        DevSxeLog::INFO(json_encode(['code' => $code, 'resp' => $result]), __FILE__, __LINE__, __METHOD__);
        if ($result['error'] != 0) {
            return array();
        }

        $tokenInfo = json_decode($result['data'], true);
        if (isset($tokenInfo['errcode']) && $tokenInfo['errcode'] != 0) {
            return array();
        }
        return $tokenInfo;
    }

    /**
     * 授权用户信息
     *
     * @param  $token
     * @param  $openId
     */
    private function _authUserInfo($token, $openId)
    {
        $urlStr = 'access_token=' . $token . '&openid=' . $openId . '&lang=zh_CN';
        $result = Curl::get(3012, ['getUrl' => $urlStr]);
        if ($result['error'] != 0) {
            return array();
        }

        $userInfo = json_decode($result['data'], true);
        if (isset($userInfo['errcode']) && $userInfo['errcode'] != 0) {
            return array();
        }
        return $userInfo;
    }

}
