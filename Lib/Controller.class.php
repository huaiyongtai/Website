<?php

/**
 * 控制器基础类
 *
 * 控制器基础类，封装了常用的操作（载入数据模型、数据校验、客户端传递参数、客户端返回数据格式定义）
 * 数据校验：常见的数据类型（整型、电话、邮编、身份证等）和开发测试专有数据（学习卡、53客服卡等）
 * 客户端提交参数：所有的GP的以数组方式封装在成员变量 {@link $params}中例：
 *      $_POST['stu_id']、$_GET['name']
 *      $this->params['stu_id']、$this->params['name']
 * 客户端返回数据格式定义：{@link $responeData}
 *
 */

namespace DevSxe\Lib;

abstract class Controller
{

    /**
     * 保存客户端传递的变量，包括GET/POST/PUT等
     *
     * @access proteted
     * @var array
     * @name $params
     */
    protected $params;

    public function __construct()
    {
        return;
    }

    /**
     * 返回客户端分页数据格式定义
     *
     * 针对所有请求查询列表定义的数据格式
     * 数据定义
     *      stat    返回状态 0失败异常、1执行成功
     *      rows    返回总记录数
     *      data    番薯数据信息
     * 样例：
     * 正常情况：
     *      有数据：
     *         array(
     *             'stat' => 1,
     *             'rows' => 总记录数,
     *             'data' => 查询数据列表,
     *         )
     *
     *      查询无数据：
     *         array(
     *             'stat' => 1,
     *             'rows' => 0,
     *             'data' => ’‘,
     *         )
     *
     *      执行异常：
     *         array(
     *             'stat' => 0,
     *             'rows' => 0,
     *             'data' => ’异常情况或错误提示‘,
     *         )
     *
     * @access protected
     * @var array
     * @name $pagingData
     */
    protected $pagingData = array(
        'stat' => 1,
        'rows' => 0,
        'data' => array(),
    );

    /**
     * 设置客户端传递参数
     *
     * @params array $params 参数
     * @return array [int:status, mixted string]
     */
    public function setParams($params)
    {
        //防止LOGID参数对业务逻辑产生影响
        if (isset($params['LOGID'])) {
            unset($params['LOGID']);
        }

        $this->params = $params;
        return $this;
    }

}
