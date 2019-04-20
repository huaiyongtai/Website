<?php

/*
 * 消息任务处理
 */

namespace DevSxe\Application\Component\Wx;

class MsgTask
{

    /**
     * 根据id获取对应任务
     * @param type $id
     * @return type
     */
    public function task($id)
    {
        if (!is_numeric($id)) {
            return array();
        }
        $msgTaskM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $task = $msgTaskM->get($id);
        return $task;
    }

    public function cancelSend($id)
    {
        $taskInfo = $this->task($id);
        if (empty($taskInfo)) {
            return array(
                'stat' => 0,
                'data' => '未找到该任务',
            );
        }
        if ($taskInfo['status'] != 1) {
            return array(
                'stat' => 0,
                'data' => '该任务状态不允许修改',
            );
        }

        $startTime = strtotime($taskInfo['start_time']);
        $cancelTime = strtotime(date('Y-m-d H:i:s'));
        if ($startTime - $cancelTime < 1) {
            return array(
                'stat' => 0,
                'data' => '任务发送前1分钟不允许取消',
            );
        }

        $this->signTaskOver($id, 0, 3);
        return array(
            'stat' => 1,
            'data' => 'success',
        );
    }

    /**
     * 根据任务类型读取一个待执行任务
     * @param  int $type 任务类型
     * @return array 返回所有有效任务
     */
    public function nextExecuTaskType($type)
    {
        $msgTaskM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $task = $msgTaskM->nextTaskType($type);

        return $task;
    }

    /**
     * 根据任务类型读取所有符合条件的任务
     * @param  int $type 任务类型
     * @return array 返回所有有效任务
     */
    public function readTasksByType($type)
    {
        $msgTaskC = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $tasks = $msgTaskC->tasksByType($type);
        return $tasks;
    }

    /**
     * 标记任务为结束状态
     * @param  int $taskId 任务id
     * @param  int $range  任务影响数
     * @param  int $status 结束状态 2->正常结束， 3->手动终止，
     */
    public function signTaskOver($taskId, $range, $status = 2)
    {
        $params = array(
            'id' => $taskId,
            'status' => $status,
            'range' => $range,
            'end_time' => date('Y-m-d H:i:s'),
        );

        $msgTaskC = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $result = $msgTaskC->update($params);
        return $result;
    }

    public function jobSize()
    {
        $msgC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TemplateMsg');
        return $msgC->tplQueueSize();
    }

    /**
     * 执行消息作业(暂时只处理模板消息)
     */
    public function excuAllJobs($type = 1)
    {
        if ($type != 1) {
            return false;
        }

        $msgC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TemplateMsg');
        while (true) {
            $tplMsg = $msgC->popFromQueue();
            if (empty($tplMsg)) {
                break;
            }
            $msgC->sendStandardMsg($tplMsg);
        }

        return true;
    }

    /**
     * 将作业推入消息队列(暂时只处理模板消息)
     */
    public function pushJobsToQueue($task, $users)
    {
        if ($task['type'] != 1) {
            return;
        }

        //1. 消息优先级
        $type = $task['priority'] == 2 ? 2 : 1;

        //2. 消息接受者信息
        $receivers = array();
        foreach ($users as $value) {
            $receivers[]['openId'] = $value['user_id'];
        }

        //3. 消息信息
        $content = json_decode($task['content'], true);

        //4. 消息入队
        $msgC = \DevSxe\Lib\G('\DevSxe\Application\Component\Wx\TemplateMsg');
        $result = $msgC->addMsgsToQueue($content['msgId'], $receivers, $content['data'], $type);
        return $result;
    }

    /**
     * 添加任务
     * @param array $params 任务信息
     * @return 成功 返回自增id 失败返回false
     */
    public function addTask($params)
    {

        $taskInfo = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $taskInfo[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                continue;
            }
            $taskInfo[$key] = $value;
        }

        $msgTaskC = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $result = $msgTaskC->add($taskInfo);
        return $result;
    }

    /**
     * 任务列表
     * @param array $params 查询信息
     */
    public function taskListInfo($params)
    {
        $where = 'WHERE 1=1';
        if (!empty($params['finds']) && is_array($params['finds'])) {
            foreach ($params['finds'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                if (is_string($value)) {
                    $where .= ' AND ' . $key . ' LIKE ' . "'%$value%'";
                    continue;
                }
                foreach ($value as $val) {
                    $where .= ' AND ' . $key . ' LIKE ' . "'%$val%'";
                }
            }
        }
        if (!isset($params['page']) && !isset($params['length'])) {
            return array(
                'stat' => 0,
                'data' => '缺少必要参数',
            );
        }

        if (!is_numeric($params['page']) && !is_numeric($params['length'])) {
            return array(
                'stat' => 0,
                'data' => '参数格式不正确',
            );
        }

        $length = $params['length'];
        $offset = ($params['page'] - 1) * $length;

        $msgTaskM = \DevSxe\Lib\G('\DevSxe\Application\Model\Wx\MsgTask');
        $taskList = $msgTaskM->taskList($offset, $length, $where);
        $total = $msgTaskM->taskTotal($where);
        return array(
            'stat' => 1,
            'data' => array(
                'list' => $taskList,
                'total' => $total,
            )
        );
    }

}
