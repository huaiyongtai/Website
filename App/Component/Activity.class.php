<?php

/**
 * 微信-热点活动
 */

namespace DevSxe\Application\Component\Wx;

class Activity
{

    /**
     * 根据config参数获取特定的活动列表
     * @param $config array(page=>1, length=>20)
     * @return 活动列表
     */
    public function getList($config)
    {
        $page = $config['page'] <= 0 ? 1 : $config['page'];
        $length = $config['length'] <= 0 ? 1 : $config['length'];

        $offset = ($page - 1) * $length;
        if ($offset >= $this->totalCount()) {
            return array(
                'stat' => 0,
                'data' => '查找起始位置异常'
            );
        }

        $activityM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        $list = $activityM->getList($offset, $length);
        if ($list == false) {
            return array(
                'stat' => 0,
                'data' => '查找异常',
            );
        }

        return array(
            'stat' => 1,
            'data' => $list,
        );
    }

    /**
     * 添加活动
     */
    public function add($params)
    {
        $activityM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        return $activityM->add($params);
    }

    /**
     * 通过包含id字段的参数的更新活动信息
     */
    public function update($params)
    {
        $activityM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        return $activityM->update($params);
    }

    /**
     * 获取指定$id的活动信息
     * @return
     */
    public function get($id)
    {
        $activityM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        $info = $activityM->get($id);

        if ($info == false) {
            return array(
                'stat' => 0,
                'data' => '获取异常',
            );
        }

        return array(
            'stat' => 1,
            'data' => $info[0],
        );
    }

    /**
     * 根据活动名称获取所有匹配的活动信息
     */
    public function getByName($name)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        return $menuM->getByName($name);
    }

    /**
     * 是否存在$name的活动
     */
    public function isExistName($name)
    {
        $nodes = $this->getByName($name);
        return !empty($nodes);
    }

    /**
     * 获取活动总数
     */
    public function totalCount()
    {
        $activityM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Activity');
        return $activityM->totalCount();
    }

    /**
     * 预览活动消息
     */
    public function preview($id)
    {
        //取得当前活动信息
        $curInfo = $this->get($id);
        if ($curInfo['stat'] == 0) {
            return $curInfo;
        }
        $replyC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\Reply');
        return $replyC->preCustomMsg($curInfo['data']);
    }

}
