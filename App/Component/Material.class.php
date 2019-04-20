<?php

/*
 * 微信-图文信息
 */

namespace DevSxe\Application\Component\Wx;

class Material
{

    /**
     * 获取图文素材列表
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

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        $list = $replyM->getList($config['wxId'], $offset, $length);
        if ($list == false) {
            return ['stat' => 0, 'data' => '查找异常'];
        }

        return array(
            'stat' => 1,
            'data' => [
                'listInfo' => $list,
                'total' => $total,
            ]
        );
    }

    /**
     * 添加素材（注一次正能添加）
     */
    public function add($params)
    {
        if (isset($params['wxId'])) {
            $params['wx_id'] = $params['wxId'];
            unset($params['wxId']);
        }
        if (empty($params['wx_id']))  {
            return false;
        }

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        return $replyM->add($params);
    }

    /**
     * 删除
     */
    public function del($id)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        return $replyM->del($id);
    }

    /**
     * 通过id值更新素菜信息
     */
    public function update($params)
    {
        if (isset($params['wxId'])) {
            $params['wx_id'] = $params['wxId'];
            unset($params['wxId']);
        }

        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        return $replyM->update($params);
    }

    /**
     * 获取指定$id的素菜信息
     * @param type $id
     * @return type
     */
    public function get($id)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        $info = $replyM->get($id);

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
     * 根据标题获取素材
     */
    public function getByTitle($wxId, $title)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        return $replyM->getByTitle($wxId, $title);
    }

    /**
     * 是否存在对应的关键词
     */
    public function isExistTitle($wxId, $title)
    {
        $result = $this->getByTitle($wxId, $title);
        return !empty($result);
    }

    /**
     * 获取总数
     */
    public function totalCount($wxId)
    {
        $replyM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        return $replyM->totalCount($wxId);
    }

    /**
     * 获取选择图文信息
     * @return type
     */
    public function getCheckTitle($wxId)
    {
        $materialC = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        $result = $materialC->getCheckTitle($wxId);
        if ($result === false) {
            return ['stat' => 0, 'data' => '获取异常'];
        }

        return ['stat' => 1, 'data' => $result];
    }

    /**
     * 获取图文预览内容
     */
    public function preContent($ids)
    {
        $materialC = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Material');
        $newMsgs = $materialC->getByIds($ids);

        $articles = array();
        foreach ($newMsgs as $value) {
            $tempAr['title'] = $value['title'];
            $tempAr['description'] = $value['description'];
            $tempAr['url'] = $value['url'];
            $tempAr['picurl'] = $value['picurl'];
            $articles[] = $tempAr;
        }
        return array('articles' => $articles);
    }

}
