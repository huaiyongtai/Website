<?php

/**
 * 抽象的model数据模型
 *
 * 提供基本的模型增删改查的基本方法
 */

namespace DevSxe\Lib;

abstract class Model
{

    private $_db;
    private $_dbConfig;

    public function __construct()
    {
        //数据库链接
        $this->_db = new Pdodb($_dbConfig);
    }

    public function setDbConfig($dbConfig = array())
    {
        $this->_dbConfig = $dbConfig;
    }

    /**
     * 事物开始
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * 事物提交
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * 事物回滚
     */
    public function rollBack()
    {
        $this->db->rollBack();
    }

    /**
     * 插入一条数据
     */
    public function c($tbl, $params)
    {
        return $this->db->c($tbl, $params);
    }

    /**
     * 插入多条数据
     */
    public function cs($tbl, $params)
    {
        return $this->db->cs($tbl, $params);
    }

    /**
     * 获取最后插入的ID
     * @return int 
     */
    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

}
