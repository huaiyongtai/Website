<?php

namespace DevSxe\Lib;

use \DevSxe\Lib\Config;

class Curl
{

    //配置信息
    static protected $conf;
    //初始化curl
    static protected $ch;
    //http请求超时时间设置，以秒为单位
    static protected $seconds = 60;
    //http请求的URL
    static protected $url = '';
    //http请求的参数
    static protected $params = '';

    /**
     * 读取配置信息
     *
     * @param type $type
     */
    private static function _getConf($type)
    {

        //读取配置
        $conf = Config::get('\DevSxe\Application\Config\Storage\Curl\\' . $type);

        if (empty($conf) || empty($conf['url'])) {
            throw new \Exception($type . ' not found');
        }

        self::$conf = $conf;
    }

    /**
     * curl基本操作
     *
     * @param type $mode 请求方式（1:POST,2:GET）
     * @param type $option 传递的其他参数
     *                          array(
     *                              'getUrl' => 请求地址时需要的get参数，字符串形式拼接，一般用于get值会变化的情况下，示例：getUrl = '?token=aaaa&note=abc',
     *                          )
     */
    private static function _curl($mode = 1, $option = array())
    {

        //请求地址
        if (!empty($option['getUrl'])) {
            self::$url = self::$conf['url'] . $option['getUrl'];
        } else {
            self::$url = self::$conf['url'];
        }

        //超时时间
        self::$seconds = !empty(self::$conf['timeout']) ? self::$conf['timeout'] : self::$seconds;

        //初始化curl
        self::$ch = curl_init();

        //请求URL
        curl_setopt(self::$ch, CURLOPT_URL, self::$url);

        //设置header头信息
        self::setHeader();

        //设置超时
        curl_setopt(self::$ch, CURLOPT_TIMEOUT, self::$seconds);
        //显示输出结果
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

        if ($mode == 1) {
            //使用POST方式传输
            curl_setopt(self::$ch, CURLOPT_POST, true);
            //POST传输数据
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, self::$params);
        }

        if (!empty(self::$conf['httpHeader'])) {
            curl_setopt(self::$ch, CURLOPT_HTTPHEADER, self::$conf['httpHeader']);
        }

        //需要SSL认证
        if (!empty(self::$conf['useSsl'])) {
            //SSL证书认证
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, true);
            //严格认证
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 2);
            //验证的文件地址
            curl_setopt(self::$ch, CURLOPT_CAINFO, self::$conf['sslCertPath']);
        } else {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        //需要pem证书双向认证
        if (!empty(self::$conf['useCert'])) {
            //证书的类型
            curl_setopt(self::$ch, CURLOPT_SSLCERTTYPE, 'PEM');
            //PEM文件地址
            curl_setopt(self::$ch, CURLOPT_SSLCERT, self::$conf['sslCertPath']);
            //私钥的加密类型
            curl_setopt(self::$ch, CURLOPT_SSLKEYTYPE, 'PEM');
            //私钥地址
            curl_setopt(self::$ch, CURLOPT_SSLKEY, self::$conf['sslKeyPath']);
        }

        $stime = getMicrotime();
        $data = curl_exec(self::$ch);
        $etime = getMicrotime();
        self::_curlLog($stime, $etime, self::$url);
        $error = curl_errno(self::$ch);
        if (!empty($error)) {
            if (class_exists('\DevSxe\Service\Log\DevSxeLog')) {
                $curlInfo = ['url' => self::$url, 'errorno' => $error, 'errormsg' => curl_error(self::$ch)];
                $serverInfo = ['server_name' => $_SERVER['SERVER_NAME'], 'cur_pc' => getmypid()];
                \DevSxe\Service\Log\DevSxeLog::INFO(json_encode(array_merge($curlInfo, $serverInfo)), __FILE__, __LINE__);
            }
        }

        curl_close(self::$ch);
        return array('data' => $data, 'error' => $error);
    }

    /**
     * 根据配置中是否有appid设置鉴权header头
     */
    private static function setHeader()
    {
        if (!empty(self::$conf['params']['appid'])) {
            curl_setopt(self::$ch, CURLOPT_HEADER, false);
            curl_setopt(self::$ch, CURLOPT_HTTPHEADER, self::getAuthHeaders());
        } else {
            //过滤HTTP头
            curl_setopt(self::$ch, CURLOPT_HEADER, false);
        }
    }

    /**
     * 获取鉴权头信息
     */
    private static function getAuthHeaders()
    {
        $appid = self::$conf['params']['appid'];
        $appkey = self::$conf['params']['appkey'];
        $time = time();
        $sign = md5($appid . '&' . $time . $appkey);

        return [
            'X-Auth-Appid: ' . $appid,
            'X-Auth-TimeStamp: ' . $time,
            'X-Auth-Sign: ' . $sign
        ];
    }

    /**
     * POST请求
     *
     * @param type $type 业务类型
     * @param type $params 请求的POST参数
     * @param type $option 传递的其他参数
     *                          array(
     *                              'getUrl' => 请求地址时需要的get参数，字符串形式拼接，一般用于get值会变化的情况下，示例：getUrl = '?token=aaaa&note=abc',
     *                          )
     * @return type
     */
    public static function post($type, $params = array(), $option = array())
    {

        //读取配置
        self::_getConf($type);

        //请求参数
        if (!empty(self::$conf['params'])) {
            $params = array_merge($params, self::$conf['params']);
        }

        //请求的数组转换格式
        $format = !empty(self::$conf['format']) ? self::$conf['format'] : 1;
        switch ($format) {
            case 1:
                self::$params = http_build_query($params);
                break;
            case 2:
                self::$params = json_encode($params, JSON_UNESCAPED_UNICODE);
                break;
            case 3:
                //self::$params = "<xml>";
                $xmlRoot = !(empty(self::$conf['xmlRoot'])) ? self::$conf['xmlRoot'] : 'xml';
                self::$params = "<" .$xmlRoot  . ">";
                foreach ($params as $key => $val) {
                    if (is_numeric($val)) {
                        self::$params .= "<" . $key . ">" . $val . "</" . $key . ">";
                    } else {
                        self::$params .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                    }
                }
                //self::$params .="</xml>";
                self::$params .= "</" .$xmlRoot  . ">";
                break;
            default:
                break;
        }

        //请求curl
        return self::_curl(1, $option);
    }

    /**
     * GET请求
     *
     * @param type $type 业务类型
     * @param type $option
     */
    public static function get($type, $option = array())
    {

        //读取配置
        self::_getConf($type);

        //请求curl
        return self::_curl(2, $option);
    }

    /**
     * 日志输出
     *
     * @param type $stime
     * @param type $etime
     * @param type $url
     */
    private static function _curlLog($stime, $etime, $url)
    {
        $time = $etime - $stime;
        $time = sprintf('%.3f', $time * 1000);
        Core::$curl[] = array(
            'url' => $url,
            'time' => $time,
        );
    }

}
