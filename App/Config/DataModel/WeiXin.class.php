<?php

/**
 * 微信
 */
return array(

    'token' => array(
        'key' => 'wx_token_%s',
        'type' => 'hash',
        'paramNum' => 1,
        'saveMode' => 1,
        'cacheEngine' => '\DevSxe\Application\Config\Storage\Default\redisdb',
        'cacheFields' => array(
            'id', 'wx_id', 'token_id', 'expires_end',
        ),
        'isSave' => 1,
        'dbName' => 'wx_token',
        'dbEngine' => '\DevSxe\Application\Config\Storage\Default\weixin',
        'dbFields' => array(
            'id', 'wx_id', 'token_id', 'expires_end', 'expires_in', 'expires_start',
        ),
    ),

    //QRCode-临时二维码（老存储结构）
    'qrCode' => array(
        'key' => 'wx_ticket_%d_%d',
        'type' => 'hash',
        'saveMode' => 1,
        'paramNum' => 2,
        'cacheEngine' => '\DevSxe\Application\Config\Storage\Default\redisdb',
        'cacheFields' => array(
            'ticket_id', 'expires_end',
        ),
        'isSave' => true,
        'dbName' => 'wx_qr_code',
        'dbEngine' => '\DevSxe\Application\Config\Storage\Default\weixin',
        'dbFields' => array(
            'id', 'stu_id', 'ticket_id', 'scene_id', 'expires_in', 'expires_start', 'expires_end',
        ),
    ),

    //模板消息队列
    'wxTplMsgQueue' => array(
        'key' => 'wx_tpl_msg_queue',
        'paramNum' => 0,
        'type' => 'list',
        'isSave' => false,
        'cacheEngine' => '\DevSxe\Application\Config\Storage\Default\redisdb',
    ),
    //临时素材
    'tmpImg' => array(
        'key' => 'wx_tmpImg_%s',
        'type' => 'hash',
        'paramNum' => 1,
        'saveMode' => 1,
        'cacheEngine' => '\DevSxe\Application\Config\Storage\Default\redisdb',
        'cacheFields' => array(
            'media_id', 'create_time', 'end_time','nickname','stu_id','is_used','headimgurl','canGetTicket','isReceive',
        ),
        'isSave' => 0,
    ),

    //临时开发测试账号
    'wx_tmpStu' => array(
        'key' => 'wx_tmpStu_%s',
        'type' => 'hash',
        'paramNum' => 1,
        'saveMode' => 1,
        'cacheEngine' => '\DevSxe\Application\Config\Storage\Default\redisdb',
        'cacheFields' => array(
            'openid','status','create_time','modify_time','openid_1','openid_2','openid_3','is_finish','parents_id','is_Send','is_receiveTicket',
        ),
        'isSave' => 0,
    ),
);
