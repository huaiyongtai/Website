<?php

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Model;
use DevSxe\Lib\Config;

class WebOAuth extends  Model
{
    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 添加授权日志
     * @return bool
     */
    public function addAuthLog($params)
    {
        $params['create_time'] = date('Y-m-d H:i:s');
        $result = $this->db->c('wx_auth_log', $params);
        return $result ? $this->getLastInsertId() : false;
    }
    
    /**
     * 获取授权信息
     * @return bool
     */
    public function userInfos($openIds, $type, $source)
    {
        if (empty($openIds)) {
            return array();
        }
        $openIdsStr = implode("','", $openIds);
        $sql = <<<DEVSXE
            SELECT * 
            FROM (
                SELECT * FROM `wx_auth_log` 
                WHERE `type` =:type AND `openid` IN ('{$openIdsStr}') AND `source` =:source 
                ORDER BY id DESC
            ) user 
            GROUP BY `openid`;
DEVSXE;
        $params = [
            ['type', $type, \PDO::PARAM_INT],
            ['source', $source, \PDO::PARAM_INT],
        ];
        $result = $this->db->g($sql, $params);
        return $result;
    }

}
