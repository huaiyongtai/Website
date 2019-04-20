<?php

/**
 * 微信-模板消息
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Config;
use \DevSxe\Lib\Storage;
use \DevSxe\Service\Log\DevSxeLog;

class TemplateMsg extends Message
{

    /**
     * 发送模板消息
     * @param int $stuId 学生id
     * @param array $tplMsg 消息内容
     * @return array(
     *     'stat' => -1001, //-1xxx 参数错误 -2xxx 用户信息错误 -3xxx 消息过滤异常 -4xxx 其他错误 0成功 正数微信返错误
     *     'data' => '',    //发送结果具体信息
     * );
     *
     */
    public function sendRawMsg($stuId, $tplMsg)
    {
        //0. 校验
        if (empty($stuId) || !is_numeric($stuId)) {
            return array(
                'stat' => -1002,
                'data' => 'error, stuId is not valid',
            );
        }
        if (empty($tplMsg) || !is_array($tplMsg)) {
            return array(
                'stat' => -1003,
                'data' => 'error, template format is not valid',
            );
        }

        //1. 查找对应用户
        $tplUser = $this->_tplUser($stuId, 2);
        if ($tplUser['stat'] == 0) {
            return array(
                'stat' => -2001,
                'data' => $tplUser['data'],
            );
        }

        //2. 发送消息
        $tplMsg['touser'] = $tplUser['data']['plat_user_id'];
        $result = $this->sendStandardMsg($tplMsg, Account::DEFAULT_WX_ID_SIGN);
        if (!$result) {
            return array(
                'stat' => -4001,
                'data' => 'error, curl send fail',
            );
        }
        return array(
            'stat' => $result['errcode'],
            'data' => $result['errmsg'],
        );
    }

    /**
     * 发送模型消息
     * @param array  $msgInfo 待发送的模板信息
	 * @param int 	 $pattern 发送模式，1. 立即发送，2. 将放到队列中自动发送
     * @param string $type    发送源，默认为开发测试服务号发送
     * +++++++++++++++++++++++++++++++++++++++++++++++++++++++
     * + $msgInfo 数组格式示例如下：
     * + $msgInfo = array(
     * +      'stuIds' => array(1, 2, 3, ....), //接受消息的学生Id数组
     * +      'msgId' => 1101,                  //模板Id值，该值应和模板配置中的相同
     * +      'data' => array(                  //消息内容，若有未传入字段将从配置文件中读取（注：字段必须和配置文件相同）
     * +          'url' => 'http://domain.com',
     * +          'first' => '你好同学，有直播课了...',
     * +          'keyword1' => 'xxxx',
     * +          'keyword2' => 'xxxx',
     * +          'keyword3' => 'xxxx',
     * +          'remark'   => '',
     * +      )
     * + );
     * +++++++++++++++++++++++++++++++++++++++++++++++++++++++
     */
    public function sendMsg($msgInfo, $pattern = 1, $type = Account::DEFAULT_WX_ID_SIGN)
    {
        //1.消息接受用户
        $receivers = $this->_getReceiversByStuIds($msgInfo['stuIds']);
        if (empty($receivers)) {
            return array(
                'stat' => 0,
                'data' => '指定用户未关联微信！'
            );
        }

        //2.确定发送模板
        $tplMsgs = $this->_assembledTplMsgs($msgInfo['msgId'], $receivers, $msgInfo['data']);
        if ($tplMsgs == false) {
            return array(
                'stat' => 0,
                'data' => '模板组装异常',
            );
        }

        //3.发送消息
        foreach ($tplMsgs as $tplMsg) {
			if ($pattern == 2) {
				$this->pushToQueue($tplMsg);
				continue;
			}
            $this->sendStandardMsg($tplMsg, $type);
        }
    }
	
	/**
     * 发送模板消息（日志收集）
     * @param array $tplMsg  标准模板消息(完整的模板，组装好的模板)
     * @param int   $type    发送类型测试、线上
     */
    public function sendStandardMsg($tplMsg, $type = Account::DEFAULT_WX_ID_SIGN)
    {
        //1. 过滤
        $filterResutl = $this->filterMsg($tplMsg);
        if ($filterResutl['stat'] == 0) {
            return array(
                'stat' => 0,
                'data' => $filterResutl['data'],
            );
        }
        //2. 发送
        $data = $filterResutl['data'];
        if (empty($data)) {
            return array(
                'stat' => 0,
                'data' => '消息内容不能为空',
            );
        }
        $result = $this->send(3003, $data, $type);
        //3. 记录日志
        $status = $result ? $result['errcode'] : -1;
        $this->_saveLog($tplMsg['touser'] . '---' . $status);
        return $result;
    }

    /**
     * 添加消息到发送队列中
     * @param  int $msgId       模板id
     * @param  array $receivers 消息接受者
     * @param  array $msgInfo   消息信息
     * @param  int $type        添加方式 (1->添加到队头，2->队尾)
     * @return void
     */
    public function addMsgsToQueue($msgId, $receivers, $msgInfo, $type)
    {
        $tplMsgs = $this->_assembledTplMsgs($msgId, $receivers, $msgInfo);
        if ($tplMsgs == false) {
            return array(
                'stat' => 0,
                'data' => '模板组装异常',
            );
        }

        foreach ($tplMsgs as $tplMsg) {
            $this->pushToQueue($tplMsg, $type);
        }
    }

    /**
     * 发送队列
     */
    public function tplQueueSize()
    {
        $result = Storage::lsize('\DevSxe\Application\Config\DataModel\WeiXin\wxTplMsgQueue');
        return $result;
    }

    /**
     * 添加消息到发送队列中
     * @param  array $tplMsg
     * @param  int   $type
     */
    public function pushToQueue($tplMsg, $type = 1)
    {
        $msg = json_encode($tplMsg);
        Storage::lPush('\DevSxe\Application\Config\DataModel\WeiXin\wxTplMsgQueue', '', $msg, $type);
    }

    /**
     * 消息出队列
     * @return 模板消息
     */
    public function popFromQueue()
    {
        $result = Storage::lPop('\DevSxe\Application\Config\DataModel\WeiXin\wxTplMsgQueue');
        return json_decode($result, true);
    }

    /**
     * @deprecated 2016-11-10 请在Component\Wx\User调用该方法
     * 检测未绑定微信学生,和已绑定微信学生
     * @param  array $stuIds 学生Id数组
     * return
     * +  array(
     * +      'bindWx' => array(1, 2, 3, ....),
     * +      'notBindWx' => array(2,3,4,5),
     * + );
     */
    public function splitBindWxStuIds($stuIds)
    {
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        return $userC->splitBindWxStuIds($stuIds);
    }

    /**
     * 消息过滤(暂无过滤规则)
     * @param array $tplMsg 模板消息
     * 代码返回格式
     * @return array(
     *       'stat' => 1,
     *       'data' => $tplMsg,
     * );
     */
    public function filterMsg($tplMsg)
    {
        return array(
            'stat' => 1,
            'data' => $tplMsg,
        );
    }

    /**
     * 模板用户信息
     * @param int $id 可以为openId,也可以为学生id，默认为OpenId
     * @param int $idType 依赖id类型，$id为学生Id $idType=2, $id为OpenId $idType=1
     * @return 用户信息
     */
    private function _tplUser($id, $idType)
    {
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $userInfo = $userC->getBindInfoById($id, $idType);
        if (empty($userInfo)) {
            return array(
                'stat' => 0,
                'data' => 'error, this stuId not bind',
            );
        }

        return array(
            'stat' => 1,
            'data' => $userInfo,
        );
    }

    /**
     * 根据用户和模板修改内容组装模板
     *
     * @param string $msgId 模板消息Id(配置Id)
     * @param array  $receivers 消息接受者
     * @param array  $tplModInfo 修改模板信息
     * @return 模板消息数组
     */
    private function _assembledTplMsgs($msgId, $receivers, $tplModInfo = array())
    {
        //1.确定模板
        $tplInfo = $conf = Config::get('\DevSxe\Application\Config\Config\WxTemplate\\' . $msgId);
        if (empty($tplInfo)) {
            return false;
        }

        //1). 模板消息结构
        $tplMsg = array(
            'url' => '',
            'data' => '',
            'touser' => '',
            'template_id' => $tplInfo['templateId'],
        );
        //2). 组装消息模板(url、data)
		
        $tplMsg['url'] = isset($tplModInfo['url']) ? $tplModInfo['url'] : $tplInfo['url'];
		if ($tplMsg['url'] == -1) {
			$tplMsg['url'] = '';
		}
        foreach ($tplInfo['data'] as $key => $val) {
            $tplMsg['data'][$key] = array(
                'value' => (isset($tplModInfo[$key]) ? $tplModInfo[$key] : $val),
                'color' => $tplInfo['color'],
            );
        }
        //3). 组装消息接受者模板(touser, first重拼)
        $tplMsgs = array(); //待发送模板数组
        foreach ($receivers as $receiver) {
            $receiverTplMsg = $tplMsg;
            //touser
            $receiverTplMsg['touser'] = $receiver['openId'];
            //first重拼
            $realName = empty($receiver['name']) ? '' : $receiver['name'];
            $first = $receiverTplMsg['data']['first']['value'];
            $receiverTplMsg['data']['first']['value'] = sprintf($first, $realName);
            $tplMsgs[] = $receiverTplMsg;
        }
        return $tplMsgs;
    }

    /**
     * 根据学生 Id 获取绑定微信的学生信息
     * @param $stuIds 可以为单个学生
     * @return 未绑定->false 已绑定->用户信息
     */
    private function _getReceiversByStuIds($stuIds)
    {
        if (empty($stuIds)) {
            return array();
        }

        //1. 去重
        $filterIds = array_flip(array_flip($stuIds));

        //2. 获取绑定信息
        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $bindInfo = $platUserC->bindMapsByStuIds($filterIds, 8);
        if (empty($bindInfo)) {
            return array();
        }

        //3. 获取用户的称谓信息
        $bindIds = array_keys($bindInfo);
        $stuInfoC = \DevSxe\Lib\G('\DevSxe\Application\Component\Student\Base\Info');
        $appellations = [];
        if (count($bindIds) < 20) {
            foreach ($bindIds as $stuId) {
                $appellations[$stuId]['realname'] = $stuInfoC->getRealnameById($stuId);
            }
        } else {
            $appellations = $stuInfoC->appellations($bindIds);
        }

        //4. 组装用户信息
        $receviers = array();
        $index = 0;
        foreach ($bindInfo as $stuId => $openId) {
            $receviers[$index]['openId'] = $openId;
            if (empty($appellations[$stuId])) {
                $receviers[$index]['name'] = '';
                $index++;
                continue;
            }
            $nameInfo = $appellations[$stuId];
            $name = $nameInfo['realname'];
            if (empty($name)) {
                $name = $nameInfo['nickname'] ? $nameInfo['nickname'] : '';
            }
            $receviers[$index]['name'] = $name;
            $index++;
        }
        return $receviers;
    }

    /**
     * 操作结果日志记录
     */
    private function _saveLog($content)
    {
        $date = date('Ymd');
        $time = date('H:i:s');
        $host = gethostname();
        list($usec, $sec) = explode(" ", microtime());
        $microtimeFloat = ((float) $usec + (float) $sec);

        $errStr = "执行时间：\n";
        $errStr .= "时分秒：{$time}\n";
        $errStr .= "时间戳和微秒数：{$microtimeFloat}\n";
        $errStr .= "{$content}\n";
        $logDir = \DevSxe\Lib\R('\DevSxe\Application\Config\LogPath');
        $path = $logDir['logPath'] . $host . '_' . $date . getmypid() . '.log';
        file_put_contents($path, $errStr, FILE_APPEND);
    }
}
