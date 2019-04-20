<?php

return array(
    'default' => array(
        'dsn' => 'mysql:host=dev-ceshi;dbname=ceshi;',
        'user' => 'ceshi',
        'psw' => 'ceshi2',
        'type' => 'mysql',
        'charset' => 'utf8',
        'engine' => 'pdodb',
    ),
    'redisCacheDefalut' => array(
        'host' => 'dev-ceshi',
        'port' => 6379,
        'dbIndex' => 0,
        'engine' => '\DevSxe\Lib\Redisdb',
    ),
);
