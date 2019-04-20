<?php

namespace DevSxe\Lib;

/**
 * 查看是否加载了msgpack扩展，如果没有，重写序列化函数。msgpack扩展，一个类似的数据对象序列化扩展，被传统的josn容量小，cpu耗时更小
 */
if (!function_exists('msgpack_serialize')) {

    function msgpack_serialize($str)
    {
        return json_encode($str);
    }

}

if (!function_exists('msgpack_unserialize')) {

    function msgpack_unserialize($str)
    {
        return json_decode($str, true);
    }

}

/**
 * 返回当前unix时间戳和微秒数
 * @params void
 * return floa 微秒数
 */
function getMicrotime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}

/**
 *  请求日志
 * 	@return void
 */
function myRequestHandler()
{
    $data = array(
        'total_time' => Core::$time['system']['total'],
        'db_time' => Core::$time['mysql']['time'],
        'db_cnt' => Core::$time['mysql']['cnt'],
        'db_details' => msgpack_serialize(Core::$time['mysql']['details']),
        'mem_use' => Core::$memory['mem_use'],
        'mem_peak_use' => Core::$memory['mem_peak_use'],
        'controller' => Core::$c['controller'],
        'action' => Core::$c['method'],
        'cake_controller' => Core::$cake_c['controller'],
        'cake_action' => Core::$cake_c['method'],
        'quest_time' => date('Y-m-d H:i:s'),
        'quest_user' => '',
    );


    if (Core::$conf['log'] == 1) {
        log::savelog($data, 1);
    }
}

/**
 * 载入一个对象，并返回该对象
 *
 * 如果对象不存在，根据相对应规则，载入对应的类文件，并实例化该类。所有的对象维护在一个全局的静态变量数据中
 * $className命名规则为类名成+对象类型（控制器、模型、第三方、核心等）
 * 控制器：C   ordersC  controller
 * 模  型：M   ordersM  model
 * 第三方：V   ordersV  vendor
 * 核  心：L   ordersL  lib
 * 配  置：N   ordersN  config
 *
 * @params string $className 实例类名称
 * @return object
 */
function G($className, $params = array())
{
    $objectHash = md5($className . json_encode($params));
    if (!isset(\DevSxe\Lib\Core::$objects[$objectHash])) {
        \DevSxe\Lib\Core::$objects[$objectHash] = new $className($params);
    }
    return \DevSxe\Lib\Core::$objects[$objectHash];
    return new $className($params);
    $obType = substr($className, -1);
    $className = substr($className, 0, strlen($className) - 1);


    if (!isset(Core::$objects['objects'][$obType][$className])) {
        if ($obType == 'E') {
            $config = R('dbN');
            $params = $config[$className];
            $class = $config[$className]['engine'];
            L($class . 'E');
            Core::$objects['objects'][$obType][$className] = new $class($params);
        } else {

            L($className . $obType);
            if (strpos($className, '\\')) {
                $className = substr($className, strpos($className, '\\') + 1, strlen($className) - 1);
            }

            $className = (substr($className, strrpos($className, '/') + 1));
            if (!class_exists($className)) {

                trigger_error($className . ' 类' . '在文件' . $file . '不存在', E_USER_ERROR);
                exit;
            }

            Core::$objects['objects'][$obType][$className] = new $className($params);
        }
    }
    return Core::$objects['objects'][$obType][$className];
}

/**
 * 载入一个对象，并返回该对象
 *
 * 如果对象不存在，根据相对应规则，载入对应的类文件，并实例化该类。
 * $className命名规则为类名成+对象类型（控制器、模型、第三方、核心等）
 * 控制器：C   ordersC  controller
 * 模  型：M   ordersM  model
 * 第三方：V   ordersV  vendor
 * 核  心：L   ordersL  lib
 * 配  置：N   ordersN  config
 *
 * @params string $className 实例类名称
 * @return object
 */
function C($className, $params = null)
{
    return \DevSxe\Lib\Core::$objects[$className] = new $className($params);
}

/**
 * 载入文件
 *
 * 根据类名称载入该文件，文件名称规范
 *
 * @params string $className 文件名称
 * @return string 如果文件存在，载入文件并返回文件路径，不存在返回空
 */
function L($className)
{
    $file = DEVSXE_ROOT_DIR .
        str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)) .
        '.class.php';
    include_once $file;
}

/**
 * 载入并执行文件
 *
 * 根据类名载入并执行文件，返回结果
 *
 * @access private
 * @params string $filename 文件名称
 * @return booler
 */
function R($className)
{
    $file = DEVSXE_ROOT_DIR .
        str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4)) .
        '.class.php';
    return require $file;
}

/**
 * 修改配置
 *
 * @access public
 * @params string $key
 * @params string $val
 * @return booler
 */
function setConf($key, $val)
{
    if (isset(Core::$conf[$key])) {
        Core::$conf[$key] = $val;
    }
}

/**
 * 自定义错误处理日志，主要处理程序中未知错误
 * @params int $errNo 错误类型
 * @params string $errStr 错误描述
 * @params string $errFile 发生错误的文件
 * @params string $errLine 发生错误的行数
 * @param $errNo
 * @param $errStr
 * @param $errFile
 * @param $errLine
 * @internal param $errcontext
 */
function myErrorHandler($errNo, $errStr, $errFile, $errLine)
{
    $errInfos = array(
        'e_param' => msgpack_serialize(Core::$c['params']),
        'e_no' => $errNo,
        'e_str' => $errStr,
        'e_file' => $errFile,
        'e_line' => $errLine,
//        'e_cont' => msgpack_serialize($errcontext),
        'controller' => Core::$c['controller'],
        'method' => Core::$c['method'],
        'cake_controller' => Core::$cake_c['controller'],
        'cake_method' => Core::$cake_c['method'],
        'create_time' => date('Y-m-d H:i:s'),
    );

    $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\logs');
    $db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    $db->c('error_logs', $errInfos);
}

/**
 * check whether given file exists in include_path
 * @param $file
 */
function fileExistsInPath($file)
{
    $paths = explode(PATH_SEPARATOR, ini_get('include_path'));

    foreach ($paths as $path) {
        $fullPath = $path . DIRECTORY_SEPARATOR . $file;

        if (file_exists($fullPath)) {
            return $fullPath;
        } else if (file_exists($file)) {
            return $file;
        }
    }

    return false;
}

/**
 * 获取URL-encode之后的请求字串
 * @param array $params
 * @return string $queryStr
 */
function getQueryString($params)
{
    $arr = $params;
    unset($arr['url']);
    return http_build_query($arr, '&');
}

/**
 * convert a shorthand byte value from a php configuration directive to an integer value
 * @param string $value
 * @return int
 */
function convertBytes($value)
{
    if (is_numeric($value)) {
        return $value;
    } else {
        $digits = substr($value, 0, -1);
        $unit = strtolower(substr($value, -1));
        switch ($unit) {
            case 'k':
                $digits *= 1024;
                break;
            case 'm':
                $digits *= 1048576;
                break;
            case 'g':
                $digits *= 1073741824;
                break;
        }

        return $digits;
    }
}
