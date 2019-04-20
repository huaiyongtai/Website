<?php

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Application\Component\Wx\CommonUtilPub;

/**
 * 响应型接口基类
 */
class WxpayServerPub extends CommonUtilPub
{

    //接收到的数据，类型为关联数组
    public $data;
    //返回参数，类型为关联数组
    public $returnParameters;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 将微信的请求xml转换成关联数组，以方便数据处理
     */
    public function saveData($xml)
    {
        $this->data = $this->xmlToArray($xml);
    }

    public function checkSign()
    {
        $tmpData = $this->data;
        unset($tmpData['sign']);
        $sign = $this->getSign($tmpData); //本地签名
        if ($this->data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 获取微信的请求数据
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置返回微信的xml数据
     */
    public function setReturnParameter($parameter, $parameterValue)
    {
        $this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 生成接口参数xml
     */
    public function createXml()
    {
        return $this->arrayToXml($this->returnParameters);
    }

    /**
     * 将xml数据返回微信
     */
    public function returnXml()
    {
        $returnXml = $this->createXml();
        return $returnXml;
    }

}
