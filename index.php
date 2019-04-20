<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (empty($_GET)) {
    $_GET['url'] = 'test/testLoadToRedis';
}

define('DEVSXE_ROOT_DIR', __DIR__);

require DEVSXE_ROOT_DIR . '/Lib/Autoload.class.php';

use \DevSxe\Lib\Main;

$a = new Main(DEVSXE_ROOT_DIR);
$a->start();
