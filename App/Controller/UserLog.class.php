<?php


namespace DevSxe\Application\Controller\WeiXin;

use \DevSxe\Application\Controller\AppController;
class UserLog extends AppController
{
	public function addLog(){
		$param['phoneNum'] = $this->params['data']['phoneNum'];
		$logM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\UserLog');	
		$result = $logM->addLog($param);
		return $result;
	}


	/**
	 * 格式化数据
	 *
	 * @param $data 数据内容
	 * @param $staus 状态 0：失败 1：成功
	 */
	private function formatData($data, $stat = 0, $rows = 1)
	{
		return array('stat' => $stat, 'rows' => $rows, 'data' => $data);
	}
}
