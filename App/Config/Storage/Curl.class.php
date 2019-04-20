<?php

/**
 * 第三方cURL配置文件
 *
 * 业务ID => array(
 *                  'name' => 业务名称（必须有）
 *                  'url' => 请求第三方的地址（必须有）
 *                  'timeout' => 超时时间（无此项默认60s）
 *                  'params' => 请求第三方时的一些定制POST参数（无此项默认空数组）
 *                  'format' => POST数组转成字符串的格式（无此项默认1。1：URL-encode，2：JSON）
 *                  'useSsl => 是否使用SSL认证（无此项默认false）
 *                  'useCert' => 是否使用证书认证（无此项默认false）
 *              )
 */
return array(
    //微信公众号
    3001 => array(
        'name' => '创建菜单',
        'url' => 'https://api.weixin.qq.com/cgi-bin/menu/create?',
        'timeout' => 30,
        'format' => 2,
    ),
    3002 => array(
        'name' => '发送客服消息',
        'url' => 'https://api.weixin.qq.com/cgi-bin/message/custom/send?',
        'timeout' => 30,
        'format' => 2,
    ),
    3003 => array(
        'name' => '发送模板消息',
        'url' => 'https://api.weixin.qq.com/cgi-bin/message/template/send?',
        'timeout' => 30,
        'format' => 2,
    ),
    3004 => array(
        'name' => '获取二维码Ticket',
        'url' => 'https://api.weixin.qq.com/cgi-bin/qrcode/create?',
        'timeout' => 30,
        'format' => 2,
    ),
    3005 => array(
        'name' => '设置用户分组',
        'url' => 'https://api.weixin.qq.com/cgi-bin/groups/members/update?',
        'timeout' => 30,
        'format' => 2,
    ),
    3006 => array(
        'name' => '获取所有分组信息',
        'url' => 'https://api.weixin.qq.com/cgi-bin/groups/get?',
        'timeout' => 30,
    ),
    3007 => array(
        'name' => '刷新基础token',
        'url' => 'https://api.weixin.qq.com/cgi-bin/token?',
        'timeout' => 30,
    ),
    3008 => array(
        'name' => '网页授权token信息',
        'url' => 'https://api.weixin.qq.com/sns/oauth2/access_token?',
        'timeout' => 30,
    ),
    3009 => array(
        'name' => '永久素材列表',
        'url' => 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?',
        'timeout' => 30,
        'format' => 2,
    ),
    3010 => array(
        'name' => '获取用户列表',
        'url' => 'https://api.weixin.qq.com/cgi-bin/user/get?',
        'timeout' => 30,
    ),
    3011 => array(
        'name' => '获取用户信息',
        'url' => 'https://api.weixin.qq.com/cgi-bin/user/info?',
        'timeout' => 30,
    ),
    3012 => array(
        'name' => '授权用户信息',
        'url' => 'https://api.weixin.qq.com/sns/userinfo?',
        'timeout' => 30,
    ),
    3013 => array(
        'name' => '高清语音素材',
        'url' => 'https://api.weixin.qq.com/cgi-bin/media/get/jssdk?',
        'timeout' => 30,
    ),
);
