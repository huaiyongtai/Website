<?php

/*
 * 提供给市场推广使用
 */

namespace DevSxe\Application\Controller\WeiXin;

use DevSxe\Lib\Storage;
use \DevSxe\Application\Controller\AppController;

class Share extends AppController
{

    /**
     * 获取参加人数
     */
    public function getJionNum()
    {
        $num = Storage::incr('\DevSxe\Application\Config\DataModel\Wx\Share\marketActivitiesJoinNum', $this->params['key']);
        return $data = array('stat' => 1, 'rows' => 1, 'data' => $num);
    }

}
