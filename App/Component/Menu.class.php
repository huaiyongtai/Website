<?php

/**
 * 微信后台模块-自定义菜单
 */

namespace DevSxe\Application\Component\Wx;

use \DevSxe\Lib\Curl;
use DevSxe\Application\Component\Wx\Account;
class Menu
{

    /**
     * 获取微信菜单列表
	 * @param void 
	 * return 菜单信息列表 
     */
    public function getMenuInfo()
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        $nodes = $menuM->allNodes();
        if (empty($nodes)) {
            return ['stat' => 1, 'data' => [],];
        }

        //组装菜单显示列表
        $menuList = array();
        foreach ($nodes as $node) {
            if ($node['pid'] != 0) {
                $menuList[$node['pid']]['sub_menu'][] = $node;
                continue;
            }
			
            $subNodes = [];
			if (empty($menuList[$node['id']]['sub_menu'])) {
				$subNodes = $menuList[$node['id']]['sub_menu'];
			}	
            $menuList[$node['id']] = $node;
            if (!empty($subNodes)) {
                $menuList[$node['id']]['sub_menu'] = $subNodes;
            }
        }
        return ['stat' => 1, 'data' => array_values($menuList)];
    }

    /**
     * 获取节点信息
     * @param type $id
     */
    public function get($id)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        $result = $menuM->get($id);
        if ($result == false) {
            return ['stat' => 0, 'data' => '查找异常'];
        }

        $data = empty($id) ? $result : $result[0];
        return ['stat' => 1, 'data' => $data];
    }

    /**
     * 菜单名长度合法性判断
     * @param $name     菜单名称
     * @param $pid      菜单节点父节点id
     */
    public function isVerifyName($name, $pid)
    {
        $level = ($pid == 0 ? 1 : 2);
        $nameLength = mb_strlen($name);
        if ($nameLength < 1) {
            return false;
        }

        //区分中英文
        $maxLength = 0;
        $pattern = '/[^\x00-\x80]/';
        if (preg_match($pattern, $name)) {
            $maxLength = ($level == 1 ? 4 : 7);
        } else {
            $maxLength = ($level == 1 ? 8 : 14);
        }

        if ($nameLength > $maxLength) {
            return false;
        }

        return true;
    }

    /**
     * 获取所有顶级菜单数据
     */
    public function getPNodes()
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        $pNodes = $menuM->getPNodes();
        if ($pNodes === false) {
            return ['stat' => 0, 'data' => '顶级节点信息错误'];
        }

        return ['stat' => 1, 'data' => $pNodes];
    }

    /**
     * 获取所有顶级菜单数据
     */
    public function getByName($name)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        return $menuM->getByName($name);
    }

    public function isExistName($name)
    {
        $nodes = $this->getByName($name);
        return !empty($nodes);
    }

    /**
     * 取得对应节点的子节点的个数
     * @return 子节点个数
     */
    public function getSubNodeCount($pid)
    {
        //微信只有两级菜单，非顶级即为子节点
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        return $menuM->getSubNodeCount($pid);
    }

    /**
     * 跟新菜单数据
     */
    public function update($params)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        return $menuM->update($params);
    }

    /**
     * 菜单推送信息
     */
    public function getMenuPushMsg()
    {
        $tipM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Tips');
        $result = $tipM->getMenuPushMsg();
        if (empty($result)) {
            $result = array();
        }
        return $result;
    }

    public function isAccessDeleNode($id)
    {
        //父节点有子节点不允许删除
        if ($this->isTopNode($id) && $this->getSubNodeCount($id) > 0) {
            return false;
        }
        return true;
    }

    /**
     * 判断是否允许添加
     * @param type $pid
     */
    public function isAllowAddPNode($pid)
    {
        $count = $this->getSubNodeCount($pid);

        return ($pid == 0 ? ($count < 3) : ($count < 5));
    }

    /**
     * 删除对应id字段
     */
    public function del($id)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        return $menuM->del($id);
    }

    /**
     * 添加新的节点信息
     */
    public function add($data)
    {
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        return $menuM->add($data);
    }

    /**
     * 保存当前微信菜单列表
     * @return type
     */
    public function saveOnlineMenuList()
    {
        //获取Token
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken(Account::DEFAULT_WX_ID_SIGN);

        //获取线上菜单数据
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $token;
        $menuJsonInfo = file_get_contents($url);
        $wxMenuList = json_decode($menuJsonInfo, true);
        if ($wxMenuList['errcode']) {
            return ['stat' => 0, 'data' => '菜单列表获取失败'];
        }

        //删除本地菜单表数据
        $menuM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\Menu');
        $delResult = $menuM->del();
        if (!$delResult) {
            return ['stat' => 0, 'data' => '数据删除异常'];
        }

        //装换并添加到本地
        $menusList = $wxMenuList['menu']['button'];
        foreach ($menusList as $menu) {

            //无子节点
			$menu['pid'] = 0;
			$isInfoNode = empty($menu['sub_button']);
			$data = $this->_convertLocalNode($menu, $isInfoNode);
            $pid = $this->add($data);
            if ($isInfoNode) {
                continue;
            }

            //有子节点
            foreach ($menu['sub_button'] as $subMenu) {
				$subMenu['pid'] = $pid;
                $data = $this->_convertLocalNode($subMenu, true);
                $this->add($data);
            }
        }
        return ['stat' => 1, 'data' => '数据保存成功'];
    }

    
    /**
     * 是否为顶级菜单
     * @param   nodeId 节点Id
     * @return
     */
    public function isTopNode($nodeId)
    {
        if (empty($nodeId)) {
            return false;
        }
        $nodeInfo = $this->get($nodeId);
        return ($nodeInfo['data']['pid'] == 0);
    }

    /**
     * 创建菜单
     * @param 微信账号
     */
    public function createMenu($type)
    {
        //获取本地所有节点
        $menuInfo = $this->getMenuInfo();
        if ($menuInfo['stat'] == 0) {
            return $menuInfo;
        }

        //组装生成菜单数组
        $pushInfo = array();
        $menuList = array_slice($menuInfo['data'], 0, 3);
        foreach ($menuList as $menu) {
			$isInfoNode = empty($menu['sub_menu']);
			$topLevel = $this->_convertWxNode($menu, $isInfoNode);
			if ($isInfoNode) {
				$pushInfo[] = $topLevel;
				continue;	
			}
			//有子节点
            foreach ($menu['sub_menu'] as $subMenu) {
                $topLevel['sub_button'][] = $this->_convertWxNode($subMenu, true);
            }
            $pushInfo[] = $topLevel;
        }
	
        $wxMenuF = array('button' => $pushInfo);

        //确定是预览还是发布
        $wxId = $type == 1 ? Account::DEFAULT_TEST_ID_SIGN : Account::DEFAULT_WX_ID_SIGN;
        $result = $this->_postMenu($wxMenuF, $wxId);
        if ($result == false) {
            return ['stat' => 0, 'data' => '生成新菜单失败！'];
        }
        return ['stat' => 1, 'data' => '生成新菜单成功！'];
    }
	
	/**
     * 将微信平台的节点数据转换为本地存储的节点数据
     * @param array $node        微信格式的节点数据
     * @param bool  $isInfoNode  是否为信息节点 
	 * return array 本地节点存储信息
     */
    public function _convertLocalNode($node, $isInfoNode)
    {
        if ($isInfoNode == false) {
            return ['pid' => 0, 'name' => $node['name']];
        }

        switch($node['type']) {
            case 'click':
                $curNode['param'] = $node['key'];
				$curNode['param_type'] = 1;
                break;
            case 'view':  
                $curNode['param'] = $node['url'];
				$curNode['param_type'] = 2;
                break;
            case 'miniprogram':
				$param = [
					'url' => $node['url'],
					'appid' => $node['appid'],
					'pagepath' => $node['pagepath'],
				];
                $curNode['param']= json_encode($param);
				$curNode['param_type'] = 3;
                break;
            default:
                break;
        }   
		$curNode['pid'] = $node['pid'];
        $curNode['type'] = $node['type'];
        $curNode['name'] = $node['name'];
        return $curNode;
    }

    /**
     * 将本地节点转化为微信平台中的节点
     * @param array $node 		 当前菜单节点信息
     * @param bool  $isInfoNode  是否为信息节点 
	 * return array 微信平台节点信息 
     */
    private function _convertWxNode($node, $isInfoNode)
	{
		$curNode = []; 
		if ($isInfoNode == false) {
			$curNode['name'] = $node['name'];
			return $curNode;
		}
        switch($node['param_type']) {
            case 1:  
                $curNode['key'] = $node['param'];
                break;
            case 2:
                $curNode['url'] = $node['param'];
                break;
            case 3:
                $param = json_decode($node['param'], true);
                $curNode['url'] = $param['url'];
                $curNode['appid'] = $param['appid'];
                $curNode['pagepath'] = $param['pagepath'];
                break;
            default:
                break;
        }   
        $curNode['type'] = $node['type'];
        $curNode['name'] = $node['name'];
        return $curNode;
    }

    /**
     * 生成菜单
     * @param $menu     菜单数组  Curl负责json序列化
     * @param $wxId     带生成菜单账号
     * @return bool     成功->true, 失败->false
     */
    public function _postMenu($menu, $wxId)
    {
        //获取Token
        $tokenMgr = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TokenMgr');
        $token = $tokenMgr->getAccessToken($wxId);
        if ($token == false) {
            return false;
        }
        $option = array('getUrl' => ('access_token=' . $token));
        $result = Curl::post(3001, $menu, $option);
        if ($result['error'] != 0) {
            return false;
        }
        $result['data'] = json_decode($result['data'], true);
        if (($result['data']['errcode'] != 0)) {
            return false;
        }
        return true;
    }

}
