<?php

/**
 * 永久二维码说明
 *      1. 永久二维码最大值为100000（目前参数只支持1--100000）
 *      2. 永久二维码只携带场景信息（不携带学生id）,用于来源统计等场所
 *      3. sceneId
 *          0  ~ 1W, 内部使用
 *          1W ~ 2W, 运营使用 
 */

namespace DevSxe\Application\Component\Wx;

use DevSxe\Application\Component\Wx\QRCode;

class SceneQRCode
{

    /**
     * 获取二维码列表
     */
    public function getList($config, $type)
    {
        $page = $config['page'] <= 0 ? 1 : $config['page'];
        $length = $config['length'] <= 0 ? 1 : $config['length'];
        $offset = ($page - 1) * $length;
        if ($offset >= $this->getCountByType($type)) {
            return array(
                'stat' => 0,
                'data' => '没有更多的元素'
            );
        }
        
        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        $list = $sceneQrM->getList($offset, $length, $type);
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
     * 添加
     */
    public function add($params, $type)
    {
        if (empty($params) || empty($type)) {
            return array(
                'stat' => 0,
                'data' => '添加二维的参数不正确',
            );
        }
        
        //1. 获取场景id
        $sceneInfo = $this->_getValidSceneIdByType($type);
        if ($sceneInfo['stat'] == 0) {
            return $sceneInfo;
        }
        $sceneId = $sceneInfo['data'];
        //2. 创建二维码
        $qrcodeC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\QRCode');
        $result = $qrcodeC->createTicket($sceneId, QRCode::QRCODETYPEFOREVER);
        if ($result == false) {
            return array(
                'stat' => 0,
                'data' => '创建二维码时失败',
            );
        }

        //3. 入库
        $params['type'] = $type;
        $params['scene_id'] = $sceneId;
        $params['url'] = $result['url'];
        $params['ticket'] = $result['ticket'];

        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        return $sceneQrM->add($params);
    }

    /**
     * 通过id值更新二维码信息
     */
    public function update($params)
    {
        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');

        return $sceneQrM->update($params);
    }

    /**
     * 获取指定$id的二维码信息
     */
    public function get($id)
    {
        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        $info = $sceneQrM->get($id);
        if ($info == false) {
            return array(
                'stat' => 0,
                'data' => '获取异常',
            );
        }

        return array(
            'stat' => 1,
            'data' => $info,
        );
    }

    /**
     * 根据标题二维码信息
     */
    public function getByTitle($title)
    {
        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        return $sceneQrM->getByTitle($title);
    }

    /**
     * 是否存在对应的关键词
     */
    public function isExistTitle($title)
    {
        $result = $this->getByTitle($title);
        return !empty($result);
    }

    /**
     * 获取总数
     */
    public function getCountByType($type)
    {
        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        return $sceneQrM->getCountByType($type);
    }

    /**
     * 根据类型获取有效场景id（目前对$type=2的二维码进行处理）
     * @param int $type 类型 1->内部， 2->运营
     * @return int 场景id
     */
    private function _getValidSceneIdByType($type)
    {

        if ($type != 2) {
            return array(
                'stat' => 0,
                'data' => '该类型二维码未经过审核',
            );
        }

        $sceneQrM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\SceneQRCode');
        $sceneId = $sceneQrM->getLastSceneIdByType($type);
        if ($sceneId == false) {
            $sceneId = 10001;
        }

        if ($sceneId >= 20000) {
            return array(
                'stat' => 0,
                'data' => '该类型二维码超出业务限制'
            );
        }

        $sceneId++;
        return array(
            'stat' => 1,
            'data' => $sceneId
        );
    }

}
