<?php

/**
 * 微信-自助服务
 */

namespace DevSxe\Application\Model\Wx;

use DevSxe\Lib\Config;
use DevSxe\Lib\Model;

class AutoService extends Model
{

    public function __construct()
    {
        $dbConfig = Config::get('\DevSxe\Application\Config\Storage\Default\weixin');
        $this->db = \DevSxe\Lib\G('\DevSxe\Lib\Pdodb', $dbConfig);
    }

	/**
     * 批量添加服务
     * @return bool
     */
    public function batchAdds($cells)
    {
        $result = $this->db->cs('wx_auto_service', $cells);
        return $result;
    }

	/**
     * 清除表中的数据
     * @return bool
     */
    public function clearData()
    {
		$params = [];
		$sql = 'DELETE FROM `wx_auto_service`';
        $result = $this->db->ud($sql, $params);
        return $result;
    }

	/**
     * 查找
     * @return 失败->false, 成功->整个记录
     */
    public function getByStuId($stuId)
	{
		$sql = <<<DEVSXE
            SELECT *
            FROM `wx_auto_service`
			WHERE `stu_id` = :stuId
            ORDER BY `id` DESC
            LIMIT 1;
DEVSXE;
		$params = array(
            array('stuId', $stuId, \PDO::PARAM_INT),
        );
        $result = $this->db->g($sql, $params);
        return $result[0];
    }

	/**
     * 获取数据列表
     */
    public function listInfo($offset, $length, $where = '1=1')
    {
        $sql = <<<DEVSXE
            SELECT *
            FROM `wx_auto_service`
			WHERE {$where}
            ORDER BY `id` DESC
            LIMIT :offset, {$length}
DEVSXE;
        $params = array(
            array('offset', $offset, \PDO::PARAM_INT),
        );
        $result = $this->db->g($sql, $params);
        return $result;
    }


	/**
     * 获取数据总数
     */
    public function total($where)
	{
		$sql = <<<DEVSXE
            SELECT COUNT(*) AS total
            FROM `wx_auto_service`
			WHERE {$where}
DEVSXE;
        $result = $this->db->g($sql, array());
        return $result[0]['total'];
    }

}

