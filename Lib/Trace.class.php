<?php

namespace DevSxe\Lib;

class Trace
{

    private $_objects;

    /**
     * 中间结果数据
     */
    private $_traceData = array();

    /**
     * 方法集合
     */
    private $_traceMethod = array();

    /**
     * 返回的最终数据
     */
    private $_data;

    public function __construct($class = null)
    {
        $this->_objects = $class;
    }

    public function __tostring()
    {
        if (!is_array($this->_data) || is_object($this->_data) || is_resource($this->_data)) {
            return (string) $this->_data;
        }

        return json_encode($this->_data);
    }

    public function __call($funcs, $args = array())
    {
        if (empty($args)) {
            $args = array($this->_data);
        }

        if (!is_array($funcs)) {
            $result = call_user_func_array(array($this->_objects, $funcs), $args);
        } else {
            $result = call_user_func_array($funcs, $args);
        }

        if (empty($result)) {
            throw new \Exception('返回值非法');
        }

        if ((int) $result[0] > 0) {
            throw new \Exception('执行错误');
        }

        $this->_traceData[] = $this->_data = $result;
        return $this;
    }

    public function traces()
    {
        return $this->_traceData;
    }

}
