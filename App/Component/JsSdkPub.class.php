<?php

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Application\Component\Wx\CommonUtilPub;
use \DevSxe\Lib\Config;

/**
 * 分享到朋友圈
 * modify: 2018-2-12 09:50:35
 * 增加发送给朋友、qq、qq空间的授权
 */
class JsSdkPub extends CommonUtilPub
{

    /**
     * 是否开启debug 字符串
     */
    public $debug = 'false';

    /**
     * access_token是公众号的全局唯一票据，公众号调用各接口时都需使用access_token
     */
    public $accessToken;

    /**
     * jsapi_ticket票据
     */
    public $jsTicket;

    /**
     * jssdk参数，格式为json
     */
    public $parameters;

    /**
     * js接口列表
     */
    public $jsApiList = array(
        'onMenuShareTimeline',
        'startRecord', 
        'stopRecord',
        'onVoiceRecordEnd',
        'uploadVoice',
        'onMenuShareAppMessage',
        'onMenuShareQQ',
        'onMenuShareQZone'
    );

    /**
     * 当前地址
     */
    public $url;

    /**
     * curl超时时间
     */
    public $curlTimeout;

    public function __construct()
    {
        parent::__construct();
        //设置curl超时时间
        $this->curlTimeout = $this->wxPayConf['curlTimeout'];
    }

    /**
     * 	是否开启debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * 	设置access_token
     */
    public function setToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * 	设置js_ticket
     */
    public function setTicket($ticket)
    {
        $this->jsTicket = $ticket;
    }

    /**
     * 	设置分享地址
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * 	设置js接口列表
     */
    public function setJsApiList($jsApiList)
    {
        $this->jsApiList = $jsApiList;
    }

    /**
     * 生成签名
     *
     * 步骤1. 对所有待签名参数按照字段名的ASCII 码从小到大排序（字典序）后，使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串string1：
     * 步骤2. 对string1进行sha1签名，得到signature
     *
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        //使用URL键值对的格式
        $urlString = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：sha1加密
        return sha1($urlString);
    }

    /**
     * 	设置wx.config的参数
     */
    public function getParameters()
    {
        //生成js对象
        $jsSdkObj = array();
        //签名
        $jsSignObj = array();
        $jsSignObj['noncestr'] = $this->createNoncestr();
        $jsSignObj['jsapi_ticket'] = $this->jsTicket;
        $jsSignObj['timestamp'] = time();
        $jsSignObj['url'] = $this->url;
        //wxConf
        $jsSdkObj['debug'] = $this->debug;
        $jsSdkObj['signature'] = $this->getSign($jsSignObj);
        $defaultId = Config::get('\DevSxe\Application\Config\Config\WxAccount\default');
        $defaultInfoC = \DevSxe\Lib\G('DevSxe\Application\Component\Wx\Account');
        $defaultData = $defaultInfoC->authInfo($defaultId);
        $jsSdkObj['appId'] = $defaultData['APP_ID'];
        $jsSdkObj['timeStamp'] = $jsSignObj['timestamp'];
        $jsSdkObj['nonceStr'] = $jsSignObj['noncestr'];
        $jsSdkObj['jsApiList'] = $this->jsApiList;
        return $jsSdkObj;
    }

}
