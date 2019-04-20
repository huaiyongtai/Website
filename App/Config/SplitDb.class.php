<?php
/**
 * 数据库分表配置文件
 * field	拆分字段,如果为id，则默认为按照自增量id来拆分
 * is_auto_id	是否同步主表自增ID
 * number	拆分表数目
 * ploy		拆分策略
 * is_master 是否存在主表 true:false
 * m_fileds array('field1', 'field2', 'field3',)主表所需字段,is_master为true有用
 * master_db 主表所在库，目前默认为同一服务器
 * split_db  拆分表所在库
 * separate_field_point 切分字段临界点
 * separate_number_add 切分表新增表数目
 * separate_to_new 只切分表数据到新扩展表 1:是 0:否
 */

return array(
	'devsxe_stu_infos' => array(
		'field'		=> 'stu_id',
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'split_master',
		'split_db'	=> 'split_slave',
	),
	'devsxe_msg' => array(
		'field'		=> 'id',
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => false,
		'm_fields'	=> array(),
		'master_db' => 'split_master',
		'split_db'	=> 'split_slave',
	),
	'devsxe_dynamics' => array(
		'field'		=> 'stu_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),	
	'devsxe_dynamic_contents' => array(
		'field'		=> 'id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => false,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),	
	'devsxe_dyn_comments' => array(
		'field'		=> 'dyn_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),	
	'devsxe_dyn_comment_contents' => array(
		'field'		=> 'id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
	'devsxe_collects' => array(
		'field'		=> 'stu_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
	'devsxe_follows' => array(
		'field'		=> 'stu_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
	'devsxe_student_visitors' => array(
		'field'		=> 'stu_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'log',
		'split_db'	=> 'log',
	),
	'devsxe_teacher_visitors' => array(
		'field'		=> 'teacher_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'log',
		'split_db'	=> 'log',
	),
	'devsxe_dyn_release_replys' => array(
		'field'		=> 'release_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
	'devsxe_dyn_reply_releases' => array(
		'field'		=> 'reply_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
	'devsxe_ats' => array(
		'field'		=> 'to_id',
		'is_auto_id'=> 0,
		'number'	=> 50,
		'ploy'		=> 'mod',
		'is_master' => true,
		'm_fields'	=> array(),
		'master_db' => 'message',
		'split_db'	=> 'message',
	),
//	'devsxe_highlights' => array(
//		'field'		=> 'section_id',
//		'is_auto_id'=> 1,
//		'number'	=> 50,
//		'ploy'		=> 'mod',
//		'is_master' => true,
//		'm_fields'	=> array(),
//		'master_db' => 'message',
//		'split_db'	=> 'message',
//	),
);
