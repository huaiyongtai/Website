<?php

/**
 * 微信-消息处理类基类
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Curl;

class Message
{
    //发送消息时所需的Token
    public $tokenMgr;

    public function __construct()
    {
        $this->tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
    }

    /**
     * 发送消息
     * @param int    $postCode    模块编码
     * @param array  $msgContent  消息内容
     * @param string $wxId        指定微信号
     * @return bool 请求成功返回请求结果， 失败false
     */
    public function send($postCode, $msgContent, $wxId)
    {
        //获取对应type值得Token
        $token = $this->tokenMgr->getAccessToken($wxId);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));

        //开启发送请求
        $result = Curl::post($postCode, $msgContent, $option);
        if ($result['error'] != 0) {
            return false;
        }

        //处理返回结果
        return json_decode($result['data'], true);
    }

}
