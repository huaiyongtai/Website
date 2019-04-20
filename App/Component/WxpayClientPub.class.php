<?php

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Application\Component\Wx\CommonUtilPub;

/**
 * 请求型接口的基类
 */
class WxpayClientPub extends CommonUtilPub
{

    public $parameters; //请求参数，类型为关联数组
    public $response; //微信返回的响应
    public $result; //返回参数，类型为关联数组
    public $url; //接口链接
    public $curl_timeout; //curl超时时间

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 	作用：设置请求参数
     */
    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    public function createXml()
    {
        $this->parameters['appid'] = $this->wxPayConf['appId']; //公众账号ID
        $this->parameters['mch_id'] = $this->wxPayConf['mchId']; //商户号
        $this->parameters['nonce_str'] = $this->createNoncestr(); //随机字符串
        $this->parameters['sign'] = $this->getSign($this->parameters); //签名
        return $this->arrayToXml($this->parameters);
    }

    /**
     * 	作用：post请求xml
     */
    public function postXml()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     * 	作用：使用证书post请求xml
     */
    public function postXmlSSL()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlSSLCurl($xml, $this->url, $this->curl_timeout);
        return $this->response;
    }

    /**
     * 	作用：获取结果，默认不使用证书
     */
    public function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }

}
