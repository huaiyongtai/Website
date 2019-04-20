<?php

namespace DevSxe\Lib;

use DevSxe\Lib\Core;

include_once DEVSXE_ROOT_DIR . '/Lib/Core.class.php';
include_once DEVSXE_ROOT_DIR . '/Lib/Functions.class.php';
include_once DEVSXE_ROOT_DIR . '/Lib/OldCache.class.php';

class Main
{

    private $libDir = '';
    private $appDir = '../';
    private $logDir = '';
    private $stime; //记录程序执行时间
    private $etime;

    public function __construct($libDir = './')
    {
        $this->stime = getMicrotime();
        $this->logDir = \DevSxe\Lib\R('\DevSxe\Application\Config\LogPath');

        //初始化devsxeLog
        $this->initLogger();

        \DevSxe\Lib\MyErrors::run();
        \DevSxe\Lib\MyException::run();
    }

    public function start()
    {

        register_shutdown_function(function() {
            $last = error_get_last();
            if ($last['type'] == E_ERROR || $last['type'] == E_CORE_ERROR || $last['type'] == E_COMPILE_ERROR || $last['type'] == E_PARSE) {
                // additional 2M memory in case of OOM
                ini_set('memory_limit', convertBytes(ini_get('memory_limit')) + 2097152);
                if (class_exists('\DevSxe\Service\Log\DevSxeLog')) {
                    $mes = MyErrors::getLogMes($last['message']);

                    \DevSxe\Service\Log\DevSxeLog::error($mes, $last['file'], $last['line']);
                }
            }

            if (isset($this->logDir['print']) && $this->logDir['print'] == true) {
                Core::$data['errors'] = \DevSxe\Lib\MyErrors::getAllInfos();
                if ($last['type'] == E_ERROR) {
                    $last['debugtrace'] = debug_backtrace();
                    array_unshift(Core::$data['errors'], $last);
                }
                Core::$data['exception'] = \DevSxe\Lib\MyException::getAllInfos();
                Core::$data['total']['mysql'] = Core::$time['mysql'];
                Core::$data['total']['redis'] = Core::$time['redis'];
                Core::$data['total']['curl'] = Core::$curl;
            }

            $this->etime = getMicrotime();

            if (class_exists('\DevSxe\Service\Log\DevSxeLog')) {
                $delimiter = chr(30) . ' ';
                $log = '[execTime] => ' . sprintf("%.3f", ($this->etime - $this->stime) * 1000) . $delimiter;
                $log .= '[redisCount] => ' . Core::$time['redis']['cnt'] . $delimiter;
                $log .= '[redisTime] => ' . Core::$time['redis']['time'];
                \DevSxe\Service\Log\DevSxeLog::info($log, __FILE__, __LINE__);
            }

//            echo '<pre>';
//            print_r(Core::$data);
            
            if (isset($_SERVER['HTTP_RESPONSE_TYPE'])&&$_SERVER['HTTP_RESPONSE_TYPE']==='application/json') {
                if (isset(Core::$data['params']['debug']) && Core::$data['params']['debug'] == 2) {
                    echo json_encode(Core::$data);
            } else {
                $data = array(
                    'data' => Core::$data['data'],
                );                

                echo json_encode($data);
                }

            }else{
               if (isset(Core::$data['params']['debug']) && Core::$data['params']['debug'] == 2) {
                    echo msgpack_serialize(Core::$data);
                } else {
                    $data = array(
                        'data' => Core::$data['data'],
                    );         

                    echo msgpack_serialize($data);
                } 
            }
        });

        $this->dispatch();
    }

    protected function dispatch()
    {

        $params = explode('/', trim($_GET['url'], '/'));

        $route = \DevSxe\Lib\R('\DevSxe\Application\Config\Route');
        if (isset($route[$params[0]])) {
            $controller = $route[$params[0]];
        } else {
            switch (count($params)) {
                case '2':
                    $controller = '\DevSxe\Application\Controller\Www\\' . $params[0];
                    break;
                case '3':
                    $controller = '\DevSxe\Application\Controller\\' . $params[2] . '\\' . $params[0];
                    break;
            }
        }

        Core::$data['url'] = $_GET['url'];
        Core::$data['params'] = $_REQUEST;
        Core::$data['API_ip'] = !empty($_SERVER['REMOTE_ADDR']) ? : '';
        Core::$data['data'] = \DevSxe\Lib\G($controller)->setParams($_REQUEST)->$params[1]();

        return;
    }

    /**
     * 初始化devsxeLog
     */
    protected function initLogger()
    {
        // 引入日志库并初始化配置
        if (fileExistsInPath('Log/DevSxeLog.php')) {
            //包含DevSxeLog文件
            include_once 'Log/DevSxeLog.php';
            \DevSxe\Service\Log\DevSxeLog::config($this->logDir['devsxeLog']);

            //取hostname第二个字段作为业务名,后期删除
            $arr = explode('-', gethostname());
            if (!empty($arr[1])) {
                \DevSxe\Service\Log\DevSxeLog::config(array(
                    'yewu' => $arr[1],
                ));
            }

            $delimiter = chr(30) . ' ';
            $log = '[message] => framework start' . $delimiter;
            $url = $params = '';
            if (isset($_REQUEST)) {
                $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
                $params = getQueryString($_REQUEST);
            }
            $log .= '[url] => ' . $url . $delimiter . '[params] => ' . $params;
            \DevSxe\Service\Log\DevSxeLog::info($log, __FILE__, __LINE__);
        }
    }

    public function __destruct()
    {

    }

}
