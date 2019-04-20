<?php

/**
 * 自动加载命名空间类的实现，根据命名空间，查找类包含的文件
 *
 * <b>使用示例</b>
 * <pre>
 * <code>
 * 	include_once DEVSXE_ROOT_DIR . 'Lib/Autoloader.php';
 * 	use namespace\classname;
 * </code>
 * </pre>
 *
 */
spl_autoload_register(function($name) {
    $className = $name;
    //Plugin/vendor中文件与项目文件命名空间统一规范
    $nameSpace = array(
        'Elasticsearch' => array('/Plugin/vendor/elasticsearch/elasticsearch/src'),
        'Psr' => array('/Plugin/vendor/psr/log'),
        'Pimple' => array('/Plugin/vendor/pimple/pimple/src'),
        'Guzzle' => array('/Plugin/vendor/guzzle/guzzle/src'),
        'Symfony' => array('/Plugin/vendor/symfony/event-dispatcher'),
        'Monolog'=>array('/Plugin/vendor/monolog/monolog/src'),
    );

    $classPrefix = substr($name, 0, strpos($name, '\\'));
    $name = str_replace('\\', '/', $name);
    if (isset($nameSpace[$classPrefix])) {
        if ($classPrefix == 'Symfony') {
            $name = substr($name, strrpos($name, '/') + 1);
        }

        $file = DEVSXE_ROOT_DIR . $nameSpace[$classPrefix][0] . DIRECTORY_SEPARATOR . $name . '.php';
    } else {
        $file = DEVSXE_ROOT_DIR . DIRECTORY_SEPARATOR . substr($name, 4) . '.class.php';
    }

    if (is_file($file)) {
        include_once($file);
        if (class_exists($className, false) || interface_exists($className, false)) {
            return true;
        } else {
            throw new Exception("{$className} 对应文件 {$file} 不存在");
        }
    }

    return false;
});
