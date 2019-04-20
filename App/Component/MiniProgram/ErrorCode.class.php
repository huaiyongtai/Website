<?php

/**
 * error code 说明.
 * <ul>

 *    <li>-41001: encodingAesKey 非法</li>
 *    <li>-41003: aes 解密失败</li>
 *    <li>-41004: 解密后得到的buffer非法</li>
 *    <li>-41005: base64加密失败</li>
 *    <li>-41016: base64解密失败</li>
 * </ul>
 */

namespace DevSxe\Application\Component\Wx\MiniProgram;

class ErrorCode
{

    public  $OK = 0;
    public  $IllegalAesKey = -41001;
    public  $IllegalIv = -41002;
    public  $IllegalBuffer = -41003;
    public  $DecodeBase64Error = -41004;

}

?>