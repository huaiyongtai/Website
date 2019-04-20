<?php

/**
 * 微信-Token
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class Token extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    /**
     * 返回对应的Token
     * @param int $id token Id
     * @return 对应Token记录
     */
    public function get($id)
    {
        $sql = 'SELECT * FROM wx_token where id = ' . "'$id'";
        $result = $this->db->g($sql, array());
        if (empty($result)) {
            return false;
        }
        return $result[0];
    }

    /**
     * 强制往数据表中存储一份，若数据表中存在则删除
     * @return boolean
     */
    public function forceAdd($params)
    {
        if (isset($params['id'])) {
            $delSQL = 'DELETE FROM wx_token WHERE id = ' . $params['id'];
            $result = $this->db->ud($delSQL, array());
        }

        $result = $this->c('wx_token', $params);
        return $result ? $this->getLastInsertId() : false;
    }

}
