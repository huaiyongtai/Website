<?php

/**
 * 自动服务模块
 */

namespace DevSxe\Application\Component\Wx;

class AutoService
{
	/**
	 * 获取特地区间的回复信息列表
	 * @param int $offset   查询偏移量
	 * @param int $length   查询数据量
	 * @param int $type     类型（默认全局回复）
	 * @return array 关键词列表信息数组
	 */
	public function listInfo($params)
	{
		if (empty($params)) {
			return ['stat' => 0, 'data' => '参数不能为空'];
		}
		$page = $params['page'] <= 0 ? 1 : $params['page'];
		$length = $params['length'] <= 0 ? 1 : $params['length'];
		$offset = ($page - 1) * $length;

		$where = '1=1';
		if (!empty($params['stu_id'])) {
			$where .= ' AND `stu_id` = '.$params['stu_id'];
		}
		$serviceM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\AutoService');
		$total = $serviceM->total($where);
		if ($offset > $total) {
			return ['stat' => 0, 'data' => '查找起始位置超过数据总数'];
		}

		$rawlist = $serviceM->listInfo($offset, $length, $where);
		return [
			'stat' => 1,
			'data' => ['listInfo' => $rawlist, 'total' => $total]
		];
	}

	public function getByStuId($stuId)
	{
		$serviceM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\AutoService');
		$cell = $serviceM->getByStuId($stuId);
		return $cell;
	}

	/**
	 * 获取详细信息
	 */
	public function detailInfo($cell) 
	{
		if (empty($cell)) {
			return [];
		}

		$detail = json_decode($cell['content'], true);
		foreach ($cell as $k => $v) {
			if ($k == 'content') {
				continue;
			}
			$detail[$k] = $v;
		}
		return $detail;
	}

	/**
	 * 批量添加服务信息
	 * @param array $addInfos 添加信息数组
	 * @param int 	$pattern  添加模式 1.清除旧数据添加 2.追加方式添加
	 */
	public function batchAdds($addInfos, $pattern, $pushType)
	{
		if (empty($addInfos)) {
			return ['stat' => 0, 'data' => '待添加内容不能为空'];
		}

		$serviceM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\AutoService');
		if ($pattern == 1) {
			$serviceM->clearData();
		}

		//1. 组装消息
		$cells = [];

		$globe = $addInfos['globe'];
		foreach ($addInfos['cells'] as $val) {
			$cell = $val;
			$cell['status'] = 1;
			$cell['create_time'] = date('Y-m-d H:i:s');
			foreach ($globe as $k => $v) {
				$cell[$k] = $v;
			}
			$cells[] = $cell;
		}
		//2. 先入库
		$dbResult = $serviceM->batchAdds($cells);
		if ($dbResult == false) {
			['stat' => 0, 'data' => '信息写入失败'];
		}

		//3. 自动推送
		if ($pushType == 1) {
			$this->_addMsgPush($addInfos['cells']);
		}
		return ['stat' => 1, 'data' => 'success'];
	}

	/**
	 * 获取成绩回复内容
	 * return string 回复内容
	 */
	public function respToUser($stuId)
	{
		$cell = $this->getByStuId($stuId);
		if (empty($cell)) {
			return '暂未查询到您的考试结果，请联系您的辅导老师。';
		}

		$stuInfoC = \DevSxe\Lib\G('\DevSxe\Application\Component\Student\Base\Info');
		$name = $stuInfoC->getRealnameById($stuId);
		$content = json_decode($cell['content'], true);
		$resp = '';
		$resp .= sprintf($content['first'], $name)."\n";
		$resp .= "\n";
		$resp .= str_replace('?', "\n\n", $content['keyword2']);
		if (!empty($content['url'])) {
			$resp .= "\n";
			$resp .= "<a href='{$content['url']}'>详情</a>";
		}
		return $resp;
	}

	/**
	 * 加入到自动发送队列 
	 * @param $cell 带加入推送信息
	 * $cell = [
	 * 	  ['stu_id' => xxx, 'content' => json消息信息],
	 * 	  ['stu_id' => xxx, 'content' => json消息信息],
	 * 	  ..... 
	 * ]
	 */
	private function _addMsgPush($cells)
	{
		//1. 获取绑定用户
		$tplMsgC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TemplateMsg');			
		foreach ($cells as $cell) {
			$msgData = json_decode($cell['content'], true);	
			// $msgData['keyword2'] = "\n".str_replace('?', "\n", $msgData['keyword2']);
			$msgData['keyword1'] = "\n".str_replace('?', "\n", $msgData['keyword2']);
			$msgData['keyword2'] = "\n".str_replace('?', "\n", $msgData['keyword2']);
			$msgInfo = [
				'data' => $msgData,	
				'msgId' => '1107',
				'stuIds' => [$cell['stu_id']],
			];
			// 1及时推送
			$tplMsgC->sendMsg($msgInfo, 2);
		}
	}
}
