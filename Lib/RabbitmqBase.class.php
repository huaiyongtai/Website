<?php

namespace DevSxe\Lib;

use \AMQPConnection;
use \AMQPChannel;
use \Exception;
use DevSxeLib\Log\DevSxeLog;

class RabbitmqBase
{

    // exchange
    protected $_exchange;

    // routing key
    protected $_routingKey;

    // queue
    protected $_queue;

    // 连接配置
    protected $_conf;

    protected $_connInstance;
    protected $_channelInstance;
    protected $_exchangeInstance;
    protected $_queueInstance;

    protected $_logger = null;

    /**
     * 简易封装，取索引的队列
     */
    public function __construct($config, $index)
    {
        $this->_exchange = $config['exchg'];
        $this->_routingKey = $config['queueInfo'][$index]['routingKey'];
        $this->_conf = $config['server'];
        $this->getChannel();

        if ($config['log']) {
            $this->_logger = new DevSxeLog($config['log']);
        }
    }

    /**
     * 加载默认配置文件
     *
     * @param string $identifier
     */
    protected function _getConfs($identifier)
    {
        $confs = include("QueueServer.php");
        $this->_conf = $confs[$identifier];
    }

    /**
     * 建立rabbitmq服务连接, 成功返回true, 若有异常则记录日志，并返回false
     */
    protected function getChannel()
    {
        if (!$this->_conf) {
            $this->_getConfs('default');
        }

        try {
            $this->_connInstance = new AMQPConnection($this->_conf);
            $this->_connInstance->connect();
            $this->_channelInstance = new AMQPChannel($this->_connInstance);
        } catch (Exception $e) {
            throw new Exception('Connect to Queue Server failed');
        }
    }

    /**
     * 关闭rabbitmq服务
     */
    protected function _closeChannel()
    {
        $this->_connInstance->disconnect();
    }

}
