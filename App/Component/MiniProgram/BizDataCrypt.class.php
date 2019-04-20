<?php

/**
 * 对微信小程序加密数据的解密.
 */

namespace DevSxe\Application\Component\Wx\MiniProgram;

class BizDataCrypt
{

    /**
     * 错误编号
     */
    private $_errorCode = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->_errorCode = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\MiniProgram\ErrorCode');
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文
     * 
     * @param $appid string 小程序的appid
     * @param $sessionKey string 用户在小程序登录后获取的会话密钥
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($appid, $sessionKey, $encryptedData, $iv, &$data)
    {
        if (strlen($sessionKey) != 24) {
            return $this->_errorCode->IllegalAesKey;
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            return $this->_errorCode->IllegalIv;
        }
        $aesIv = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $prpcrypt = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\MiniProgram\Prpcrypt');
        $result = $prpcrypt->decrypt($aesCipher, $aesKey, $aesIv);
        if ($result[0] != 0) {
            return $result[0];
        }

        $dataObj = json_decode($result[1]);
        if ($dataObj == NULL) {
            return $this->_errorCode->IllegalBuffer;
        }
        //敏感数据加上数据水印(watermark) 
        if ($dataObj->watermark->appid != $appid) {
            return $this->_errorCode->IllegalBuffer;
        }
        $data = $dataObj;
        return $this->_errorCode->OK;
    }

}
