<?php

namespace DevSxe\Lib;

use \AMQPExchange;
use \AMQPQueue;
use \AMQPConnectionException;
use \AMQPChannelException;
use \AMQPExchangeException;
use \AMQPQueueException;
use \Exception;
use DevSxeLib\Log\DevSxeLog;
include_once("./RabbitmqBase.php");
class RabbitmqProducer extends RabbitmqBase
{

    /**
     * 简易封装，取索引的队列
     */
    public function __construct($config, $index ,$_needDeclare = true)
    {
        $this->_queue = $config['queueInfo'][$index]['queues'];
        parent::__construct($config, $index);
        $this->_bindQueue($_needDeclare);
    }

    /**
     * 通过routing key来绑定exchange和queue
     *
     * @param boolean $_needDeclare 是否需要声明exchange和queue并绑定
     */
    private function _bindQueue($_needDeclare)
    {
        $this->_exchangeInstance = new AMQPExchange($this->_channelInstance);
        $this->_exchangeInstance->setName($this->_exchange);
        $this->_exchangeInstance->setType(AMQP_EX_TYPE_DIRECT);
        $this->_exchangeInstance->setFlags(AMQP_DURABLE);

        if ($_needDeclare) {
            // 声明exchange, 如果确定exchange已存在的话, 不需要重复声明, 耗时
            $this->_exchangeInstance->declareExchange();

            // 声明queue并绑定, 如果确定queue和绑定关系已存在, 不需要重复声明绑定, 耗时
            foreach ($this->_queue as $queue_name) {
                $queue = new AMQPQueue($this->_channelInstance);
                $queue->setName($queue_name);
                $queue->setFlags(AMQP_DURABLE);
                $queue->declareQueue();
                $queue->bind($this->_exchange, $this->_routingKey);
            }
        }
    }

    /**
     * 发送消息
     *
     * @param string $message
     * @return array()
     */
    public function publish($message)
    {
        if (!$message || !is_string($message)) {
            return array('stat' => 0, 'msg' => 'Params: message is invalid !');
        }

        try {
            $result = $this->_publish($message);
            return $result;
        } catch (Exception $e) {
            try {
                $this->getChannel();
                $this->_bindQueue(true);
                $res = $this->_publish($message);
                return $res;
            } catch (Exception $err) {
                return array('stat' => 0, 'msg' => $err->getMessage());
            }
        }
    }

    /**
     * 发送
     *
     * @param string $message
     * @throws \Exception
     * @return array()
     */
    private function _publish($message)
    {
        $result = $this->_exchangeInstance->publish($message, $this->_routingKey, AMQP_MANDATORY, array('delivery_mode' => 2));
        if ($result) {
            if ($this->_logger instanceof DevSxeLog) {
                $this->_logger->info('Msg: ' . $message . ' publish success!', __FILE__, __LINE__, __METHOD__);
            }

            return array('stat' => 1, 'msg' => 'Msg: ' . $message . ' publish success!');
        } else {
            if ($this->_logger instanceof DevSxeLog) {
                $this->_logger->info('Msg: ' . $message . ' publish failed!', __FILE__, __LINE__, __METHOD__);
            }

            return array('stat' => 0, 'msg' => 'Msg: ' . $message . ' publish failed!');
        }
    }

}
