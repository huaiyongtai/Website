<?php

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Model;
use DevSxe\Lib\Config;

/**
 * Class AttnGame.class
 * @author yourname
 */
class AttnGame extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

    public function addLog($params) 
    {
        $params['create_time'] = date('Y-m-d H:i:s');
        $result = $this->db->c('wx_attn_game_log', $params);
        return $result ? $this->getLastInsertId() : false;
    }

    public function joinTotal() 
    {
        $sql = <<<DEVSXE
            SELECT COUNT(DISTINCT `openid`) as total FROM `wx_attn_game_log`
DEVSXE;
        $params = [];
        $result = $this->db->g($sql, $params);
        if (empty($result)) {
            return 0;
        }
        return $result[0]['total'];
    }

    /** 
     * 获取用户的最佳纪录
     * @param  $openId     用户标识（openId）
     * @param  $chapterId  关卡章节
     * @return array       用户最佳纪录信息
     */
    public function minTime($openId, $chapterId = 0)
    {
        if ($chapterId != 0) {
            $sql = <<<DEVSXE
                SELECT * FROM `wx_attn_game_log` 
                WHERE `openid` = :openid 
                AND `chapter_id` = {$chapterId}
                ORDER BY `time` ASC LIMIT 1;
DEVSXE;
        } else {
            $sql = <<<DEVSXE
                SELECT * FROM 
                    (SELECT * FROM `wx_attn_game_log` WHERE `openid` = :openid ORDER BY `time` ASC) game 
                GROUP BY `chapter_id` 
                ORDER BY `chapter_id`;
DEVSXE;
        }

        $params = [
            ['openid', $openId, \PDO::PARAM_STR],
        ];
        $result = $this->db->g($sql, $params);
        return $result;
    }

    /**
     * 最后一次游戏纪录
     */
    public function lastRecords($openId)
    {
        $sql = <<<DEVSXE
            SELECT * FROM 
                (SELECT * FROM `wx_attn_game_log` WHERE `openid` = :openid ORDER BY `id` DESC) game 
            GROUP BY `chapter_id` 
            ORDER BY `chapter_id`;
DEVSXE;
        $params = [
            ['openid', $openId, \PDO::PARAM_STR],
        ];
        $result = $this->db->g($sql, $params);
        return $result;

    }
}
