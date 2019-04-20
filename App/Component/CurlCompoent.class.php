<?php

/**
 * Curl请求
 */
namespace DevSxe\Application\Component\Wx;

class CurlCompoent
{

    /**
     * 超时时间
     */
     private $_timeout = 5;

    /**
     * 设置请求超时时间
     *
     * @return void
     */
    public function setTimeout($timeout)
    {
        if (empty($timeout)) {
            return;
        }
        $this->_timeout = $timeout;
    }

    /**
     * GET请求
     */
    public function get($url)
    {
        //初始化curl
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


        $result = curl_exec($ch);
        $errorNo = curl_errno($ch);
        curl_close($ch);

        if (!empty($errorNo)) {
            return ['stat' => 0, 'data' => $errorNo];
        }
        return ['stat' => 1, 'data' => $result];
    }

    /**
     * 发送一个POST请求
     */
    public function post($url, $params = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        $errorNo = curl_errno($ch);
        curl_close($ch);
        if (!empty($errorNo)) {
            return ['stat' => 0, 'data' => $errorNo];
        }
        return ['stat' => 1, 'data' => $result];
    }

    /**
     * down
     */
    public function download($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $errorNo = curl_errno($ch);
        curl_close($ch); 

        if (!empty($errorNo)) {
            return ['stat' => 0, 'data' => $errorNo];
        }
        return ['stat' => 1, 'data' => $result];
    }
}
