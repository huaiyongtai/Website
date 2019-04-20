<?php
/**
 * 拆分数据表
 *
 * 根据定义规则把数据放在不同表中，规则描述如下：
 *	field	拆分字段,默认为自增量id（插入数据，先进主表获得last_id，再根据拆分算法插入分表）
 *  is_auto_id 是否为自增id 0:否 1：是
 *	number	拆分表数目
 *	ploy	拆分策略（mod：取模）
 *	is_master 是否存在主表(true:false)
 *	m_fileds  array()主表所需字段,is_master为true有用
 *	master_db 主表所在库，目前默认为同一服务器
 *	split_db  拆分表所在库
 *	point 切分字段临界点
 *	add_number 切分表新增表数目
 *
 * @todo 主表自定义字段拆分
 * 
 * @todo 实现 point、add_number，数据达到量时，重新按照策略拆分数据
 * @todo 分表默认取模，需要按照不同算法拆分处理，暂未判断
 */

namespace DevSxe\Lib;

class SplitDb
{

	/**
	 * 获取表名正则
	 */
	private $_split_reg = '/((delete|update|insert into|select\s+[\w\d\s\S]*from)\s+`?)([a-zA-Z0-9_]+)(`?\s+[\d\w\s\S]*)/im';

	/**
	 * 用来保存拆分配置
	 */
	private $_split_fields = array(
		'field'		=> '',
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => '',
		'split_db'	=> '',
	);

	/**
	 * 需要拆分的sql语句
	 */
	private $_sql = '';

	/**
	 * 绑定字段参数
	 */
	private $_params = array();

	/**
	 * 拆分规则配置
	 */
	private $_split_conf = array();

	/**
	 * 主表sql语句
	 * 分库sql语句
	 */
	private $_out_sql = array(
		'master_sql' => '',
		'split_sql'  => '',
	);

    public function __construct($splitConfig)
    {
		$this->_split_conf = $splitConfig;
                
	}

	/**
	 * 设置拆分字段配置
	 *
	 * @param array $splitConf 拆分配置
	 * @return void
	 */
	public function setSplitConf($splitConf){
		foreach($this->_split_conf as $k => $v){
			if(isset($splitConf[$k])){
				$this->_split_conf[$k] = $splitConf[$k];
			}
		}
	}

	/**
	 * 根据sql获取表名(根据拆分字段，计算出分表表名)
	 *
	 * @param string $tbl 主库表名
	 * @param array  $params 字段参数
	 * @param int    $type  1 无类型参数  2带类型参数
	 * @return string $tbl 返回分表名
	 */
	public function getSplitTbl($tbl, $params, $type=1){
		$this->_tbl = $tbl;
		$this->_params = $params;
		return $this->_getSplitTbl($type);
	}
	private function _getSplitTbl($type=0){
		/**
		 * 获取分表表名
		 * 获取分表字段值
		 */
		$splitField = '';
		if($type == 1){
			if(isset($this->_params[$this->_split_conf[$this->_tbl]['field']])){               
				$splitField = $this->_params[$this->_split_conf[$this->_tbl]['field']];
			}
		}else{
			foreach($this->_params as $k=>$v){
				if($v[0] == $this->_split_conf[$this->_tbl]['field']){
					$splitField = $v[1];
				}
			}
		}
                
		if($splitField == ''){
			//trigger_error('分表字段不存在，或格式不正确', E_USER_ERROR);
			return false;
		}

		/**
		 * 计算分表后缀
		 
                print '<pre>';
                print_r($this->_tbl);
                print_r($this->_split_conf[$this->_tbl]['is_master']);
                print_r($this->_split_fields);exit;*/
		//if($this->_split_conf['is_master']){
                    //print_r($this->_tbl . '_' . sprintf('%04d',($splitField % $this->_split_conf[$this->_tbl]['number'])));
			return $this->_tbl . '_' . sprintf('%04d',($splitField % $this->_split_conf[$this->_tbl]['number']));
		//}else{
		//	return false;
		//}
	}

	/**
	 * 获取主表插入的字段
	 *
	 * 在某些情况下，主表有可能只存储索引字段，所以插入数据需要单独处理
	 *
	 * @param string $tbl 要进行拆分的表名
	 * @param array $params 绑定的参数列表
	 * @return array 主表需要绑定的参数列表
	 */
	private function _getMasterParams($tbl, $params){
		$splitConf = R('splitN');
		/**
		 * 判断是否存在主表
		 */
		if($splitConf[$tbl]['is_master']){
			/**
			 * 根据主表字段，生成需要绑定的参数
			 */
			if(!empty($splitConf[$tbl]['m_fields'])){
				$splitParams = array();
				foreach($splitConf[$tbl]['m_fields'] as $k){
					if(empty($fields[$k])){
						trigger_error('主表绑定参数有误', E_USER_ERROR);
						return false;
					}
					$splitParams[$k] = $fields[$k];
				}
			}
		}
	}

	/**
	 * 根据sql处理拆分规则
	 *
	 * @param string $sql 执行的sql语句
	 * @param array  $params 执行的sql语句
	 * @return $string $sql 分表sql
	 */
	public function getSplitSql($sql, $params){
		$this->_sql = $sql;
		$this->_params = $params;
		unset($sql);
		unset($params);

		$splitInfos = array(
			'master' => array(
				'sql' => $this->_sql,
				'params' => $this->_params,
			),
			'split' => array(
				'sql' => '',
				'params' => $this->_params,
				'engine' => '',
			)
		);

		/**
		 * 获取表名
		 * 判断是否存在拆分规则
		 */
		if(!preg_match($this->_split_reg, $this->_sql, $matches)){
			return $this->_sql;
		}

		$sql_first_part = $matches[1];
		$this->_tbl = $matches[3];
		$sql_latter_part = $matches[4];

		/**
		 * 判断是否存在拆分规则
		 */
		if(!isset($this->_split_conf[$this->_tbl])){
			return $splitInfos;
		}

		/**
		 * 判断是否存在主表
		 * 根据主表配置字段，生成主表需绑定的参数，默认为空不改变
		 */
		if($this->_split_conf[$this->_tbl]['is_master']){
			/**
			 * 如果存在主表，检测是否存在自定义字段
			 */
			if(!empty($this->_split_conf[$this->_tbl]['m_fields'])){
				$splitInfos['master']['params'] = $this->_bulidBindParams();
			}
		}else{
			//不存在主表
			$splitInfos['master'] = array();
		}
               
		$splitTbl = $this->_getSplitTbl();
		if($splitTbl){
			$splitInfos['split']['sql'] = $sql_first_part . $this->_getSplitTbl() . $sql_latter_part;
			$splitInfos['split']['engine'] = $this->_split_conf[$this->_tbl]['split_db'];
		}
		return $splitInfos;
	}

	/**
	 * 根据表名和参数处理拆分规则
	 *
	 * @param string $tbl 表名
	 * @param array  $params 执行的sql语句
	 * @return $string array(
	 *	array(
	 *		'master_tbl' => 'tbl', 
	 *		'params' => array(
	 *			各绑定字段值	
	 *		),
	 *		'id' => '' 如果此值大于0，则按照自增量id拆分规则
	 *	array(
	 *		'split_tbl' => 'tbl', 
	 *		'params' => array(
	 *			各绑定字段值	
	 *		),
	 *	)
	 * )
	 */
	public function getSplitFromTbl($tbl, $params){
            
		$this->_tbl = $tbl;
		$this->_params = $params;
		unset($tbl);
		unset($params);

		$splitInfos = array(
			'master' => array(
				'tbl' => $this->_tbl,
				'params' => $this->_params,
				'id' => 0,
			),
			'split' => array(
				'tbl' => '',
				'params' => $this->_params,
				'engine' => '',
			)
		);

		/**
		 * 判断是否存在拆分规则
		 */
                
		if(!isset($this->_split_conf[$this->_tbl])){
                    
			return $splitInfos;
		}

		/**
		 * 判断是否存在主表
		 * 根据主表配置字段，生成主表需绑定的参数，默认为空不改变
		 */
		if($this->_split_conf[$this->_tbl]['is_master']){
			/**
			 * 如果存在主表，检测是否存在自定义字段
			 */
			if(!empty($this->_split_conf[$this->_tbl]['m_fields'])){
				$splitInfos['master']['params'] = $this->_bulidBindParams();
			}

			/**
			 * 如果拆分字段为id，则默认为自增量id拆分规则,并忽略分表拆分
			 * 并返回分表所在引擎配置
			 */
			if($this->_split_conf[$this->_tbl]['is_auto_id'] == 1){
				$splitInfos['master']['id'] = 1;
				$splitInfos['split']['engine'] = $this->_split_conf[$this->_tbl]['split_db'];
				return $splitInfos;
			}
		}else{
			//不存在主表
			$splitInfos['master'] = array();
		}
		$splitInfos['split']['tbl'] = $this->_getSplitTbl(1);
		$splitInfos['split']['engine'] = $this->_split_conf[$this->_tbl]['split_db'];
                
		return $splitInfos;
	}

	/**
	 * 生成自定义绑定字段
	 *
	 * 根据拆分规则中的m_fields，生成需要绑定的字段数组
	 *
	 * @return array
	 */
	private function _bulidBindParams(){
		$params = array();
		foreach($this->_split_conf[$this->_tbl]['m_fields'] as $k){
			if(isset($this->_params[$k])){
				$params[$k] = $this->_params[$k];
			}
		}
		return $params;
	}

}
