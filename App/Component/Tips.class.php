<?php

/**
 * 微信-回复提示
 */

namespace DevSxe\Application\Component\Wx;

class Tips
{

    /**
     * 获取指定列表
     * @param type $config
     * @return type
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

        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        $list = $tipsM->getList($offset, $length);
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

    public function add($params)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        return $tipsM->add($params);
    }

    /**
     * 删除
     */
    public function del($id)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        return $tipsM->del($id);
    }

    /**
     * 通过id值更新活动信息
     */
    public function update($params)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        return $tipsM->update($params);
    }

    /**
     * 获取指定$id的信息
     * @param type $id
     * @return type
     */
    public function get($id)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        $info = $tipsM->get($id);

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
     * 获取所有顶级菜单数据
     */
    public function getByTitle($title)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        return $tipsM->getByTitle($title);
    }

    /**
     * 是否存在对应名称的记录
     */
    public function isExistTitle($title)
    {
        $result = $this->getByTitle($title);
        return !empty($result);
    }

    /**
     * 检查状态的切换限制
     * @param int $category 分类：1.关注 2.咨询 3.未绑定 4.自定义
     *                      1, 2, 3, 6 不可启用多条
     * @param int $toStatus 待切换的状态 1.关闭 2. 开启
     * @return bool
     */
    public function checkLimitStatus($category, $toStatus)
    {
        //状态关闭、4、6无限制
        if ($toStatus == 1 || $category == 4 || $category == 5) {
            return true;
        }
        $tips = $this->getValidByCategory($category);
        return count($tips) < 1;
    }

    /**
     * 通过开启状态的类别提示规则
     * @param int $category 分类：1.关注 2.咨询 3.未绑定 4.自定义
     * @return array 提示规则数组
     */
    public function getValidByCategory($category)
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        $tips = $tipsM->getOpenedByCategory($category);
        return $tips;
    }

    /**
     * 通过指定id和类别获取开启状态的回复规则
     * @param int $id       id
     * @param int $category 分类：1.关注 2.咨询 3.未绑定 4.自定义, 5. 6. 默认-1 所有类别
     * @return array 提示规则数组
     */
    public function getValid($id, $category = -1)
    {
        if (empty($id)) {
            return array();
        }

        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        $tips = $tipsM->getValidById($id);
        if (empty($tips)) {
            return array();
        }

        if ($category === -1) {
            return $tips[0];
        }

        if ($tips[0]['category'] != $category) {
            return array();
        }

        return $tips[0];
    }

    /**
     * 获取总数
     */
    public function totalCount()
    {
        $tipsM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        return $tipsM->totalCount();
    }

    /**
     * 预览消息
     */
    public function preCustomMsg($id)
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
