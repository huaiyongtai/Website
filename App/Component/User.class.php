<?php

/**
 * 微信相关的用户
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Curl;

class User
{
    /**
     * 添加一个新关注用户
     * @param array $user 添加的信息
     * @return
     *     添加失败 bool false
     *        1.$user中缺少user_id将添加失败
     *        2.若存在相同的user_id将添加失败
     *     添加成功 int  用户自增Id
     */
    public function add($user)
    {
        if (empty($user['user_id'])) {
            return false;
        }

        if ($this->isExistByOpenId($user['wx_id'], $user['user_id'])) {
            return false;
        }
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        return $userM->add($user);
    }

    /**
     * 根据用户更新用户信息
     * @param array $params 待更新的信息，若该信息未包含user_id字段，直接返回false
     * @return bool 更新是否成功
     */
    public function updateByUserId($params)
    {
        if (empty($params['user_id'])) {
            return false;
        }
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        return $userM->updateByUserId($params);
    }

    /**
     * 通过OpenId获取用户信息（注, 该接口获取的用户，不能保证该用户现在仍在关注）
     * @param string $openId 用户的OpenId
     * @return array 用户的信息数组
     */
    public function getByOpenId($wxId, $openId)
    {
        if (empty($openId)) {
            return array();
        }
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        $result = $userM->get($wxId, $openId);
        if (empty($result)) {
            return array();
        }
        return $result[0];
    }

    /**
     * 跟新用户的分组信息（更新原则:先更新公众平台的分组后更新本地分组）
     * @param int $openId 用户的user_id
     * @param int $groupId 分组id
     * @return bool 成功 true, 失败 false
     */
    public function updateGruop($wxId, $openId, $groupId)
    {
        //1. 公众平台分组更新
        //___1.1 获取token
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken($wxId);
        if ($token == false) {
            return false;
        }
        //___1.2 更新
        $wxParams = array(
            'openid' => $openId,
            'to_groupid' => $groupId,
        );
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3005, $wxParams, $option);
        if ($result['error'] != 0) {
            return false;
        }
        $wxResult = json_decode($result['data'], true);
        if ($wxResult['errcode'] != 0) {
            return false;
        }

        //2. 本地更新
        $params = array(
            'user_id' => $openId,
            'group_id' => $groupId,
        );
        return $this->updateByUserId($params);
    }

    /**
     * 从微信公众平台获取用户信息
     * @param string $openId 用户的OpenId
     * @return array 用户的信息数组
     */
    public function getFromWxByOpenId($openId, $wxId = Account::DEFAULT_WX_ID_SIGN)
    {
        if (empty($openId)) {
            return array();
        }

        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken($wxId);
        if ($token == false) {
            return array();
        }
        $option = array('getUrl' => ('access_token=' . $token. "&openid=" . $openId));
        $result = Curl::get(3011, $option);
        if ($result['error'] != 0) {
            return array();
        }
        $userInfo = json_decode($result['data'], true);
        if (!empty($userInfo['errcode'])) {
            return array();
        }
        return $userInfo;
    }

    /**
     * 根据OpenId获取用户昵称
     * @param  string $openId 用户OpenId
     * @return string 用户昵称
     */
    public function getNicknameByOpenId($openId)
    {
        $userInfo = $this->getFromWxByOpenId($openId);
        if (empty($userInfo)) {
            return '';
        }

        if ($userInfo['subscribe'] == 0) {
            return '未知';
        }
        return $userInfo['nickname'];
    }

    /**
     * 是否存在指定用户(注，用户取消关注微信服务号，用户任然存在)
     * @param string $openId 用户OpenId
     * @return bool 存在该用户 true 不存在 false
     */
    public function isExistByOpenId($wxId, $openId)
    {
        $user = $this->getByOpenId($wxId, $openId);
        return !empty($user);
    }

    /**
     * 获取用户的绑定信息
     * @param int $id 可以为openId,也可以为学生id，默认为OpenId
     * @param int $idType 依赖id类型，$id为学生Id $idType=2, $id为OpenId $idType=1
     * @return array 绑定信息
     */
    public function getBindInfoById($id, $idType = 1)
    {
        if (empty($id)) {
            return array();
        }
        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        if ($idType == 1) {
            $bindInfo = $platUserC->bindInfoByPlatKey($id, 8);
            return $bindInfo;
        }
        $bindInfos = $platUserC->bindInfosByStuId($id, array(8));
        if (isset($bindInfos[8])) {
            return $bindInfos[8];
        }
        return array();
    }

    /**
     * 检测未绑定微信学生,和已绑定微信学生
     * @param  array $stuIds 学生Id数组
     * return  array 格式示例如下：
     * +  array(
     * +      'bindWx' => array(1, 2, 3, ....),
     * +      'notBindWx' => array(2,3,4,5),
     * + );
     */
    public function splitBindWxStuIds($stuIds)
    {
        if (empty($stuIds) || !is_array($stuIds)) {
            return array(
                'bindWx' => array(),
                'notBindWx' => array(),
            );
        }
        //1. 去重
        $filterStuIds = array_flip(array_flip($stuIds));

        //2. 获取绑定信息
        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $bindInfo = $platUserC->bindMapsByStuIds($filterStuIds, 8);

        //3. 拼装返回结果
        $bindWx = array();
        $notBindWx = array();
        foreach ($filterStuIds as $stuId) {
            if (isset($bindInfo[$stuId])) {
                $bindWx[] = $stuId;
                continue;
            }
            $notBindWx[] = $stuId;
        }

        return array(
            'bindWx' => $bindWx,
            'notBindWx' => $notBindWx,
        );
    }

    /**
     * 该学生是否绑定微信
     * @param int $id 可以为openId,也可以为学生id，默认为OpenId
     * @param int $idType 依赖id类型，$id为学生Id $idType=2, $id为OpenId $idType=1
     * @return bool 绑定用户 true 未绑定在 false
     */
    public function isBindWx($id, $idType = 1)
    {
        //1. 第三方平台账号绑定查询
        $idKey = '';
        if ($idType == 1) {
            $idKey = 'platKey';
        } else if ($idType == 2) {
            $idKey = 'stuId';
        } else {
            return false;
        }

        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $result = $platUserC->checkBindStatus(array($idKey => $id), 8);
        return $result['stat'] == 1;
    }

    /**
     * 将对应学生和对应微信绑定
     * @param  string  $stuId 用户$stuId
     * @return array()
     *      array(
     *          'stat' => 0,    //0->绑定成功， 1->绑定失败
     *          'data' => '',   //绑定提示信息
     *      )
     */
    public function bindWx($stuId, $openId)
    {
        $nickName = $this->getNicknameByOpenId($openId);

        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $result = $platUserC->bindWxUser($stuId, $openId, $nickName, 8);
        return $result;
    }

    /**
     * 为用户添加金币
     * @param int $stuId 学生id
     * @param string $stuName 学生名称
     * @return void
     */
    public function increaseGold($stuId, $stuName)
    {
        if (empty($stuId) || empty($stuName)) {
            return false;
        }

        //同时给用户充100金币,微信关注奖励
        $goldInfo = array(
            'num' => 1,
            'tradeType' => 77,
            'stuId' => $stuId,
            'tradeId' => $stuName,
        );
        $achievementC = G('achievementsC');
        $achievementC->setParams($goldInfo);
        $achievementC->addStuGold();
    }

    /**
     * 获取成绩信息信息
     * @param int $openId 为openId
     * @param int $status 成绩信息是否过期 $status=0 未过期， $status=1过期， $status=-1所有信息
     * @return array 返回成绩信息
     */
    public function getScoreByOpenId($openId, $status = 0)
    {
        //1. 查找绑定用户名
        $userInfo = $this->weixinDevSxeBindGet($openId);
        if (empty($userInfo)) {
            return array();
        }
        if (!isset($userInfo['stu_name'])) {
            return array();
        }

        //2. 查询成绩
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        $result = $userM->getScore($userInfo['stu_name'], $status);
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 获取物流订单信息
     * @param int $id 可以为openId
     * @param int $status 订单状态 0已发，1完成 -1所有信息
     * @return array 返回订单信息
     */
    public function getExpressByOpenId($id, $status = 0)
    {
        //1. 查找绑定用户名
        $userInfo = $this->weixinDevSxeBindGet($id);
        if (empty($userInfo)) {
            return array();
        }

        //2. 查询订单
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        $result = $userM->getExpress($userInfo['stu_name'], $status);
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    public function getStuIdByOpenId($openId) {

        if (empty($openId)) {
            return '';
        }
        $platUserC = \DevSxe\Lib\G('\DevSxe\Application\Component\Platform\UserPlatform');
        $stuId = $platUserC->stuIdByPlatKey($openId, 8);
        if (empty($stuId)) {
            return '';
        }
        return $stuId;
    }

    /**
     * 根据平台用户Id获取对应开发测试用户详细信息
     * @param string $openId 微信用户openId
     * @return array 开发测试用户信息
     */
    public function getStuInfoByOpenId($openId)
    {
        $stuId = $this->getStuIdByOpenId($openId);
        if (empty($stuId)) {
            return array();
        }
        //1. 用户信息
        $stuInfoC = \DevSxe\Lib\G('\DevSxe\Application\Component\Student\Base\Info');
        $stuInfo = $stuInfoC->getStuInfo($stuId);

        return $stuInfo;
    }

    /**
     * 根据用户openId获取用户微信绑定信息
     * @param string $openId 用户openId
     * @return array 返回学生用户信息
     */
    public function weixinDevSxeBindGet($openId)
    {
        if (empty($openId)) {
            return array();
        }

        //0. 获取学生基本信息
        $stuInfo = $this->getStuInfoByOpenId($openId);
        if (empty($stuInfo)) {
            return array();
        }

        //1. 用户名称颜色
        $stuInfoC = \DevSxe\Lib\G('\DevSxe\Application\Component\Student\Base\Info');
        $stuNameColor = $stuInfoC->getNameColor($stuInfo['id']);
        $stuInfo['name_color'] = $stuNameColor;

        //2. 用户年级
        $gradeC = \DevSxe\Lib\G('\DevSxe\Application\Component\Basic\Grade');
        $gradeList = $gradeC->getList(1);
        $curGrade = $gradeList['data'][$stuInfo['cur_grade']]['alias'];
        $stuInfo['curGradeAlias'] = empty($curGrade) ? '' : $curGrade;

        //3. 附加信息
        $userName = $stuInfo['nickname'];
        if (empty($userName)) {
            $userName = empty($stuInfo['realname']) ? '开发测试学员' : $stuInfo['realname'];
        }
        $stuInfo['username'] = $userName;
        $stuInfo['stu_name'] = $stuInfo['name'];
        $stuInfo['devsxe_openid'] = $openId;

        return $stuInfo;
    }

    /**
     * 根据分组名称 获取分组信息
     * @param string $name 分组名称
     * @return array 分组信息
     */
    public function getGroupByName($name)
    {
        if (empty($name)) {
            return array();
        }
        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        return $userM->getGroupByName($name);
    }

    /**
     * 获取关于用户分组所有信息
     * @return array 分组信息
     */
    public function getGroupsInfo()
    {
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken(Account::DEFAULT_WX_ID_SIGN);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::get(3006, $option);
        if ($result['error'] != 0) {
            return false;
        }
        $groupsInfo = json_decode($result['data'], true);
        if (!empty($groupsInfo['errcode'])) {
            return false;
        }
        return $groupsInfo['groups'];
    }

    /**
     * 使用筛选条件筛选出符合条件的用户
     * @param  array $filter 筛选雕件
     * @return array  符合条件的用户总数信息数组
     */
    public function userCountByFilter($filter)
    {
        $whereInfo = $this->_filterToWhere($filter);
        if ($whereInfo['stat'] != 1) {
            return array(
                'stat' => 0,
                'data' => '查询条件有误'
            );
        }

        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        $count = $userM->userCountByFilter($whereInfo['data']);
        if (empty($count)) {
            return array(
                'stat' => 0,
                'data' => '未查询到符合条件的用户',
            );
        }

        return array(
            'stat' => 1,
            'data' => $count,
        );
    }

    /**
     * 根据where条件获取有效用户userId（OpenId）
     * @param  array $filter sql查询条件
     * @param  int $start  查找的起始位置
     * @param  int $length 查询的数量
     * @return array  用户userId(OpenId)数组
     */
    public function userIdsByFilter($filter, $start = 0, $length = 0)
    {
        $whereInfo = $this->_filterToWhere($filter);
        if ($whereInfo['stat'] != 1) {
            return array(
                'stat' => 0,
                'data' => '查询条件有误'
            );
        }

        $userM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\User');
        $userIds = $userM->userIdsByFilter($whereInfo['data'], $start, $length);
        if (empty($userIds)) {
            return array(
                'stat' => 0,
                'data' => '未查询到符合条件的用户',
            );
        }

        return array(
            'stat' => 1,
            'data' => $userIds,
        );
    }

    /**
     * 强condition条件转化为sql查询wehere语句
     * @param array $params 条件内容
     * @return array 转换结果
     */
    private function _filterToWhere($params)
    {
        if (empty($params) || $params['type'] < 1 || $params['type'] > 3) {
            return array(
                'stat' => 0,
                'data' => '暂不支持该类型方式查询'
            );
        }
        $where = 'WHERE 1 = 1';

        //1. 全员发送
        if ($params['type'] == 1) {

        }

        //2. 根据条件筛选
        if ($params['type'] == 2) {
            $data = empty($params['data']) ? array() : $params['data'];
            if (!empty($data['provinces']) && is_array($data['provinces'])) {
                $prosStr = '\'';
                $prosStr .= implode('\',\'', $data['provinces']);
                $prosStr .= '\'';
                $where .= ' AND province IN (' . $prosStr . ')';
            }

            if (!empty($data['cities']) && is_array($data['cities'])) {
                $citsStr = '\'';
                $citsStr .= implode('\',\'', $data['cities']);
                $citsStr .= '\'';
                $where .= ' AND city IN (' . $citsStr . ')';
            }

            if (!empty($data['groups']) && is_array($data['groups'])) {
                $groupsStr = implode(',', $data['groups']);
                $where .= ' AND group_id IN (' . $groupsStr . ')';
            }
        }

        //3. openId发送
        if ($params['type'] == 3) {
            if (empty($params['data'])) {
                return array(
                    'stat' => 0,
                    'data' => '查询条件不能为空',
                );
            }
            $openIds = '\'';
            $openIds .= implode('\',\'', $params['data']);
            $openIds .= '\'';
            $where .= ' AND user_id IN (' . $openIds . ')';
        }

        $wxId = Account::defaultWxId();
        $where .= ' ' . "AND wx_id = '{$wxId}'";
        return array(
            'stat' => 1,
            'data' => $where,
        );
    }

    /**
     * 获取指定微信号的用户openid列表
     * @return boolean
     */
    public function openIds($wxId)
    {
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken($wxId);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::get(3010, $option);
        if ($result['error'] != 0) {
            return false;
        }

        $users = json_decode($result['data'], true);
        if (!empty($users['errcode'])) {
            return false;
        }
        return $users['data']['openid'];
    }
}
