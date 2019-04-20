<?php

/**
 * 微信-二维码
 * 二维码携带场景信息：
 *  stuId 和 sceneId
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Storage;
use \DevSxe\Lib\Curl;

/**
 * 二维码场景简注（scenedId）
 * 1 => '主站小学注册',                                                                                                                               
 * 2 => '主站右侧浮动弹窗',
 * 3 => '学习中心直播辅导提醒浮层',
 * 4 => '直播小组',
 * 5 => '直播讲座',
 * 6 => '直播预约'
 * 7 => '派发优惠券',
 */
class QRCode
{
    /**
     * 临时二维码类型
     */
    const QRCODETYPETEMP = 'QR_SCENE';

    /**
     * 永久二维码
     */
    const QRCODETYPEFOREVER = 'QR_LIMIT_SCENE';

    /**
     * 获取临时二维码
     * @param string $stuId 学生Id
     * @param string $sceneId 使用场景Id
     * @return string 二维码连接
     */
    public function getCode($stuId, $sceneId)
    {

        $id = $this->combinationForSceneId($sceneId, $stuId);
        $ticket = $this->_getTicket($id);
        if ($ticket) {
            $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
            return $url;
        }
        return false;
    }

    /**
     * 解析二维码场景信息
     * **********************************
     * 目前临时二维码设计（规则）
     *      1. 临时二维码场景值必须于127
     *      2. 场景值小于50的为可带学生Id
     *      3. 场景值大于50的不带学生Id
     * **********************************
     * @param string $qrScene 原始二维码场景值
     * @return array 二维码类型、携带的信息数组
     *      临时二维码 array(
     *          type => 'QR_SCENE',
     *          data => array( stuId => '12312', sceneId => '1233')
     *      )
     *      永久二维码  array(
     *          'type' => 'QR_LIMIT_SCENE',
     *          'data' => array('sceneId' => $qrSceneId),
     *       );
     */
    public function parseQrScene($qrScene)
    {
        $qrSceneId = $qrScene;
        if (strpos($qrScene, 'qrscene_') !== false) {
            $qrSceneId = (int) substr($qrScene, 8);
        }

        //永久二维码
        if ($qrSceneId <= 100000) { //10万以下为永久类型
            return array(
                'type' => QRCode::QRCODETYPEFOREVER,
                'data' => array(
                    'sceneId' => $qrSceneId,
                ),
            );
        }

        //临时二维码
        $stuId = 0;
        $sceneId = $qrSceneId >> 25;
        if ($sceneId < 50) { //小于50的为带有uid的二维码值
            $stuId = $qrSceneId & 0x01ffffff;
        }
        return array(
            'type' => QRCode::QRCODETYPETEMP,
            'data' => array(
                'stuId' => $stuId,
                'sceneId' => $sceneId,
            ),
        );
    }

    /**
     * 合成场景信息值
     * @param int $id
     * @param int $stuId
     * @return int
     */
    public function combinationForSceneId($id, $stuId = 0, $type = QRCode::QRCODETYPETEMP) {

        if ($type != QRCode::QRCODETYPETEMP) {
            return $id;
        }
        $stdStuId = 0;
        $maxSceneId = 127;  //最大场景Id
        $stdSceneId = $id > $maxSceneId ? $maxSceneId : $id;
        if ($stdSceneId < 50) {
            $maxStuId = 33554431;   //最大学生Id
            $stdStuId = $stuId > $maxStuId ? $maxStuId : $stuId;
        }
        $sceneId = $stdSceneId << 25 | $stdStuId;
        return $sceneId;
    }

    /**
     * 创建二维码Ticket
     * @param int $sceneId     场景Id
     * @param string $type     二维码类型
     * @return 返回更新后的Ticket信息 更新失败返回 fasle
     */
    public function createTicket($sceneId, $type = QRCode::QRCODETYPETEMP)
    {
        $postData = array(
            // 'expire_seconds' => 604800, //有效期（七天, 最大可设置为30天）
            'expire_seconds' => 2592000, //有效期（业务需求现设置为30天）
            'action_name' => $type,
            'action_info' => array(
                'scene' => array(
                    'scene_id' => $sceneId
                )
            ),
        );
        if ($type != QRCode::QRCODETYPETEMP) {
            unset($postData['expire_seconds']);
        }
        //更新Ticket
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken(Account::DEFAULT_WX_ID_SIGN);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3004, $postData, $option);
        $result['data'] = json_decode($result['data'], true);
        if ($result['error'] != 0 || ($result['data']['errcode'] != 0)) {
            return false;
        }

        return $result['data'];
    }

    //================================临时二维码================================
    /**
     * 查找本地存储(缓存、数据表)的有效Ticket
     * @param int $sceneId     场景Id
     * @param string $type     二维码类型
     * @return 存在返回Ticket 不存在返回 fasle
     */
    protected function _findLocalTicket($sceneId, $type = QRCode::QRCODETYPETEMP)
    {
        if ($type != QRCode::QRCODETYPETEMP) {
            return false;
        }

        //临时
        $sceneInfo = $this->parseQrScene($sceneId);
        if ($sceneInfo['type'] != QRCode::QRCODETYPETEMP) {
            return false;
        }

        //1. 数据表
        $stdStuId = $sceneInfo['data']['stuId'];
        $stdSceneId = $sceneInfo['data']['sceneId'];
        $keysStr = $stdStuId . ',' . $stdSceneId;
        $codeInfo = Storage::hGet('\DevSxe\Application\Config\DataModel\WeiXin\qrCode', $keysStr);
        if (!empty($codeInfo['ticket_id']) && time() < $codeInfo['expires_end']) {
            return $codeInfo;
        }

        //2. 数据表
        $ticketInfo = $this->_checkCodeFromDB($stdStuId, $stdSceneId);
        if (!empty($ticketInfo) && time() < $ticketInfo['expires_end']) {
            //为缓存中写一份
            $fields = array(
                'stu_id' => array($stdStuId),
                'scene_id' => array($stdSceneId),
            );
            $keys = array('stu_id', 'scene_id');
            Storage::hInit('\DevSxe\Application\Config\DataModel\WeiXin\qrCode', $fields, $keys);

            return $ticketInfo;
        }
        return false;
    }

    /**
     * 获取ticket值
     */
    private function _getTicket($sceneId)
    {
        // 检测是否有效
        $ticket = $this->_findLocalTicket($sceneId);
        if ($ticket != false) {
            return $ticket['ticket_id'];
        }

        //更新
        $result = $this->createTicket($sceneId);
        if ($result != false) {
            $this->_saveToDb($sceneId, $result);
            return $result['ticket'];
        }

        return false;
    }

    /**
     * 临时二维码信息存储
     * @param array $sceneId 场景id
     * @param array $qrInfo  二维码信息
     */
    private function _saveToDb($sceneId, $qrInfo)
    {
        //存储
        $sceneInfo = $this->parseQrScene($sceneId);
        if ($sceneInfo['type'] != QRCode::QRCODETYPETEMP) {
            return false;
        }

        $curTime = time();
        $stdStuId = $sceneInfo['data']['stuId'];
        $stdSceneId = $sceneInfo['data']['sceneId'];

        $params = array(
            'stu_id' => $stdStuId,
            'scene_id' => $stdSceneId,
            'ticket_id' => $qrInfo['ticket'],
            'expires_in' => $qrInfo['expire_seconds'],
            'expires_end' => $curTime + $qrInfo['expire_seconds'] - 12 * 3600,
            'expires_start' => $curTime,
        );
        $params['paramsIds'] = $stdStuId . ',' . $stdSceneId;
        Storage::hAdd('\DevSxe\Application\Config\DataModel\WeiXin\qrCode', array($params));
    }

    /**
     * 检测数据表对应的Ticket信息
     * @param type $stuId
     * @param type $sceneId
     * @return 返回对应Ticket
     */
    private function _checkCodeFromDB($stuId, $sceneId)
    {
        $qrCodeM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\QRCode');
        return $qrCodeM->checkCode($stuId, $sceneId);
    }

	/**
	 * 添加扫码日志信息
	 * @params array $scanInfo 扫码信息
	 */
	public function addScanLog($scanInfo) 
	{
        $logInfo = array(
			'type' => $scanInfo['type'],
            'bind' => $scanInfo['bindStatus'],
            'Event' => $scanInfo['Event'],
            'stu_id' => $scanInfo['stuId'],
            'open_id' => $scanInfo['openId'],
            'EventKey' => $scanInfo['EventKey'],
            'scene_id' => $scanInfo['sceneId'],
            'scan_time' => date('Y-m-d H:i:s'),
            'ticket_id' => $scanInfo['ticketId'],
            'msg_main_id' => empty($scanInfo['logId']) ? '0' : $scanInfo['logId'],
        );
        $qrCodeM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\QRCode');
        $qrCodeM->addScanLog($logInfo);
	}
}
