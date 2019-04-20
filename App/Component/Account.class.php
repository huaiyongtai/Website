<?php

/**
 * 微信 账号授权
 */
namespace DevSxe\Application\Component\Wx;

use DevSxe\Lib\Config;

class Account
{
    /**
     * 默认微信号 开发测试服务号
     */
    const DEFAULT_WX_ID_SIGN = 'default';

    /**
     * 默认测试账号
     */
    const DEFAULT_TEST_ID_SIGN = 'defaultTest';

    public static function defaultTestId()
    {

        $configPath = '\DevSxe\Application\Config\Config\WxAccount';
        return Config::get($configPath . '\\' . self::DEFAULT_TEST_ID_SIGN);
    }

    public static function defaultWxId()
    {
        $configPath = '\DevSxe\Application\Config\Config\WxAccount';
        return Config::get($configPath . '\\' . self::DEFAULT_WX_ID_SIGN);
    }

    /**
     * 是否授权访问
     * @param string $wxId
     * @return boolean
     */
    public function isAuthAccess($wxId)
    {
        if (empty($this->authInfo($wxId))) {
            return false;
        }
        return true;
    }

    /**
     * 账号的授权信息
     * @param string $wxId
     * @return array
     */
    public function authInfo($wxId)
    {
        if (!isset($wxId)) {
            return array();
        }

        $configPath = '\DevSxe\Application\Config\Config\WxAccount';
        if ($wxId == Account::DEFAULT_WX_ID_SIGN || $wxId == Account::DEFAULT_TEST_ID_SIGN) {
            $wxId = Config::get($configPath. '\\' . $wxId);
        }

        $authInfo = Config::get($configPath . '\\' . $wxId);
        if (empty($authInfo)) {
            return array();
        }
        return $authInfo;
    }
}
