<?php

/**
 * 微信-自动回复
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Lib\Config;

class Reply
{
    /**
     * 缓存引擎
     */
    const KW_RESP_TOTAL_KEY = 'wx_kw_auto_resp_%s';
    const CACHE_ENGINE = '\DevSxe\Application\Config\Storage\Default\redisdb';

    /**
     * 获取指定列表
     * @param type $config
     * @return type
     */
    public function listInfo($config)
    {
        $page = $config['page'] <= 0 ? 1 : $config['page'];
        $length = $config['length'] <= 0 ? 1 : $config['length'];

        $offset = ($page - 1) * $length;
        $total = $this->totalCount($config['wxId']);
        if ($total == 0) {
            return ['stat' => 0, 'data' => '暂无数据'];
        }
        if ($offset > $total) {
            return ['stat' => 0, 'data' => '查找起始位置异常'];
        }

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        $list = $replyM->getList($config['wxId'], $offset, $length);
        if ($list == false) {
            return ['stat' => 0, 'data' => '查找异常'];
        }

        return [
            'stat' => 1,
            'data' => [
                'listInfo' => $list,
                'total' => $total,
            ]
        ];
    }

    public function add($params)
    {
        if (isset($params['wxId'])) {
            $params['wx_id'] = $params['wxId'];
            unset($params['wxId']);
        }
        if (empty($params['wx_id']))  {
            return false;
        }
        $params['content'] = trim($params['content']);
        if (isset($params['reply_ids'])) {
            $params['reply_ids'] = trim($params['reply_ids']);
        }

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->add($params);
    }

    /**
     * 删除
     */
    public function del($wxId, $id)
    {
        $cacheKey = sprintf(self::KW_RESP_TOTAL_KEY, $wxId);
        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);
        $cache->hSet($cacheKey, [$id => 0]);

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->del($id);
    }

    /**
     * 通过id值更新活动信息
     */
    public function update($params)
    {
        if (isset($params['wxId'])) {
            $params['wx_id'] = $params['wxId'];
            unset($params['wxId']);
        }
        if (isset($params['content'])) {
             $params['content'] = trim($params['content']);
        }
        if (isset($params['reply_ids'])) {
            $params['reply_ids'] = trim($params['reply_ids']);
        }

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->update($params);
    }

    /**
     * 获取指定$id的信息
     * @param type $id
     * @return type
     */
    public function get($id)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');

        $info = $replyM->get($id);
        if ($info == false) {
            return ['stat' => 0, 'data' => '获取异常'];
        }
        return ['stat' => 1, 'data' => $info[0]];
    }

    /**
     * 获取符合状态的记录信息
     * @param type $wxId
     * @param type $status
     * @return type
     */
    public function getRepliesByStatus($wxId, $status)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->getRepliesByStatus($wxId, $status);
    }

    /**
     * 根据关键词查找对应记录
     * @param type $wxId
     * @param type $kw
     * @param type $status -1 状态（多种状态）
     * @return type
     */
    public function getByKW($wxId, $kw, $status = -1)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->getByKW($wxId, $kw, $status);
    }

    /**
     * 是否存在对应的关键词
     */
    public function isExistKW($wxId, $kw)
    {
        $result = $this->getByKW($wxId, $kw);
        return !empty($result);
    }

    /**
     * 获取总数
     */
    public function totalCount($wxId)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Reply');
        return $replyM->totalCount($wxId);
    }

    /**
     * 预览消息
     */
    public function preview($id)
    {
        //取得当前活动信息
        $info = $this->get($id);
        if ($info['stat'] == 0) {
            return $info;
        }
        $curMsgInfo = $info['data'];

        $contentKey = $curMsgInfo['type'] == 1 ? 'reply_text' : 'reply_ids';
        $preMsg = [
            'type' => $curMsgInfo['type'],
            'content' => $curMsgInfo[$contentKey],
        ];

        return $this->preCustomMsg($preMsg);
    }

    public function preCustomMsg($preMsg)
    {
        if (empty($preMsg['content'])) {
            return ['stat' => 0, 'data' => '待预览的内容不能为空'];
        }

        $cusMsg = [];
        switch ($preMsg['type']) {
            case 1:
                $cusMsg['content']['content'] = $preMsg['content'];
                break;
            case 2:
                $materialC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Material');
                $cusMsg['content'] = $materialC->preContent($preMsg['content']);
                break;
            case 3:
                $cusMsg['content']['media_id'] = $preMsg['content'];
                break;
        }

        $cusMsg['type'] = $preMsg['type'];

        //预览的账号
        $wxId = Account::defaultTestId();

        //待预览用户
        $userC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\User');
        $openIds = $userC->openIds($wxId);

        $csMsgC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\CustomServiceMsg');
        foreach ($openIds as $openid) {
            $result = $csMsgC->sendMsg($wxId, $openid, $cusMsg);
            $preResult[$openid] = empty($result['errcode']) ? 'OK' : 'Failure';
        }

        //是否全部失败
        if (!in_array('OK', $preResult)) {
            return ['stat' => 0, 'data' => '全部发送失败'];
        }

        return ['stat' => 1, 'data' => $preResult];
    }

    /**
     * 关键词的响应次数
     * @param string $wxId
     * @param string $kw
     * @return int 次数
     */
    public function kwRespNumber($wxId, $kw) {

        $cacheKey = sprintf(self::KW_RESP_TOTAL_KEY, $wxId);
        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);
        $cacheTotal = $cache->hGet($cacheKey, [$kw]);
        if (empty($cacheTotal[$kw])) {
            return 0;
        }
        return $cacheTotal[$kw];
    }

    /**
     * 增加关键词的响应次数
     * @param type $wxId
     * @param type $kw
     */
    public function incrRespNumber($wxId, $kw) {

        $cacheKey = sprintf(self::KW_RESP_TOTAL_KEY, $kw);
        $curNum = $this->kwRespNumber($wxId, $kw);

        $cache = \DevSxe\Lib\G('\DevSxe\Lib\Cache', self::CACHE_ENGINE);
        $cache->hSet($cacheKey, [$kw => $curNum+1]);
    }
}
