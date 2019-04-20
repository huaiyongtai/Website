<?php

/*
 * 微信-Token管理类
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Application\Component\Wx\Account;
use \DevSxe\Lib\Storage;
use \DevSxe\Lib\Curl;

class TokenMgr
{
    /**
     * 微信号信息（待获取token的微信账号信息）
     */
    private $_accountInfo = '';

    /**
     * tokenDataModel
     */
    private $_tokenM = '\DevSxe\Application\Config\DataModel\WeiXin\token';

    /**
     * 获取基础Token
     * @param string $wxId 微信账号
     * @return 成功返回Token 失败（网络、参数错误）返回false
     */
    public function getAccessToken($wxId)
    {
        //1. 验证
        $this->setAccountInfo($wxId);
        if (empty($this->_accountInfo)) {
            return false;
        }

        //本地查找Token
        $token = $this->_searchToken();
        if ($token != false) {
            return $token;
        }

        return false;
    }

    /**
     * 使用get方式获取返回值
     * 获取网页认证的access_token值和openid值
     *  json格式 {
            "access_token":"ACCESS_TOKEN",
            "expires_in":7200,
            "refresh_token":"REFRESH_TOKEN",
            "openid":"OPENID",
            "scope":"SCOPE",
            "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
          }
     * @param string $code 认证第一步得到的code参数
     * @param string $wxId 待获取授权token的微信号
     * @return array 返回静默授权Token信息
     */
    public function getOAuthAccessToken($code, $wxId = Account::DEFAULT_WX_ID_SIGN)
    {

        //1. 验证
        $this->setAccountInfo($wxId);
        if (empty($this->_accountInfo)) {
            return false;
        }

        $urlParams = array(
            'code=' . $code,
            'appid=' . $this->_accountInfo['APP_ID'],
            'secret=' . $this->_accountInfo['APP_SERCET'],
            'grant_type=authorization_code',
        );
        $urlStr = implode('&', $urlParams);
        $result = Curl::get(3008, ['getUrl' => $urlStr]);
        if ($result['error'] != 0) {
            return false;
        }

        return json_decode($result['data'], true);
    }

    /**
     * 设置token所属账号信息
     * @param string $wxId 微信Id
     */
    public function setAccountInfo($wxId)
    {
        $accountInfoC =  \DevSxe\Lib\G('DevSxe\Application\Component\Wx\Account');
        $accountInfo = $accountInfoC->authInfo($wxId);
        if (empty($accountInfo)) {
            return false;
        }

        $this->_accountInfo = $accountInfo;
    }

    /**
     * 查找本地存储(缓存、数据表)的有效Token
     * @return 存在返回Ticket 不存在返回 fasle
     */
    private function _searchToken()
    {
        $accountInfo = $this->_accountInfo;

        //1. 本地查找
        $curTime = time();
        $cacheToken = Storage::hGet($this->_tokenM, $accountInfo['id']);
        if (!empty($cacheToken['token_id']) && $curTime < $cacheToken['expires_end']) {
            return $cacheToken['token_id'];
        }

        //2. 请求更新token
        $urlParams = [
            'grant_type=client_credential',
            'appid=' . $accountInfo['APP_ID'],
            'secret=' . $accountInfo['APP_SERCET'],
        ];
        $urlStr = implode('&', $urlParams);
        $result = Curl::get(3007, ['getUrl' => $urlStr]);
        if ($result['error'] != 0) {
            return false;
        }
        $tokenInfo = json_decode($result['data'], true);
        if (!empty($tokenInfo['errcode'])) {
            return false;
        }

        //3. 更新本地存储
        $params = array(
            'wx_id' => $accountInfo['id'],
            'token_id' => $tokenInfo['access_token'],
            'expires_in' => $tokenInfo['expires_in'],
            'expires_end' => $curTime + $tokenInfo['expires_in'],
            'expires_start' => $curTime,
        );

        if (empty($cacheToken)) {
            $params['paramsIds'] = $accountInfo['id'];
            Storage::hAdd($this->_tokenM, [$params]);
        } else {
            $params['id'] = $cacheToken['id'];
            Storage::hSet($this->_tokenM, [$params['wx_id'] => $params]);
        }
        return $tokenInfo['access_token'];
    }
}
