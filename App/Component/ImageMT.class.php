<?php

/**
 * 图片素材
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Lib\Curl;

class ImageMT
{

    /**
     * 图片素材列表
     * @return boolean
     */
    public function listInfo($params)
    {

        $page = $params['page'] <= 0 ? 1 : $params['page'];
        $length = $params['length'];
        if ($length > 20) {
            $length = 20;
        } elseif ($length <= 0) {
            $length = 1;
        }
        $offset = ($page - 1) * $length;

        $resList = [
            'type' => 'image',
            'count' => $length,
            'offset' => $offset,
        ];
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken($params['wxId']);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3009, $resList, $option);
        if ($result['error'] != 0) {
            return false;
        }

        $result['data'] = json_decode($result['data'], true);
        if (isset($result['data']['errcode'])) {
            return false;
        }
        return [
            'stat' => 1,
            'data' => [
                'total' => $result['data']['total_count'],
                'listInfo' => $result['data']['item'],
            ],
        ];
    }


}
