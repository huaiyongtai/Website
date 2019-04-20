<?php

/**
 * 对称解密使用的算法为 AES-128-CBC
 */

namespace DevSxe\Application\Component\Wx\MiniProgram;

class Prpcrypt
{
    /**
     * 错误编号
     */
    private $_errorCode = '';

    public function __construct($k)
    {
        $this->_errorCode = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\MiniProgram\ErrorCode');
    }

    /**
     * 对密文进行解密
     * 
     * @param string $aesCipher 解密的密文
     * @param string $sessionKey 解密的秘钥
     * @param string $aesIV 解密的初始向量
     * @return string 解密得到的明文
     */
    public function decrypt($aesCipher, $sessionKey, $aesIV)
    {
        try {
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            mcrypt_generic_init($module, $sessionKey, $aesIV);
            $decrypted = mdecrypt_generic($module, $aesCipher);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            return array($this->_errorCode->IllegalBuffer, null);
        }

        try {
            //去除补位字符
            $pkcEncoder = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\MiniProgram\Pkcs7Encoder');
            $result = $pkcEncoder->decode($decrypted);
        } catch (Exception $e) {
            //print $e;
            return array($this->_errorCode->IllegalBuffer, null);
        }
        return array(0, $result);
    }

}

?>