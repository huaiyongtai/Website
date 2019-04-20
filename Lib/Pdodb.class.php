<?php

/**
 * 封装了pdodb常用操作，并对select、update、delete、insert等做了简单封装，比如注入过滤，异常记录、慢日志记录等
 */

namespace DevSxe\Lib;

class Pdodb
{

    /**
     * 连接字符集
     */
    private $_charset = 'utf8';

    /**
     * 连接超时时间
     */
    private $_timeout = 600;

    /**
     * 保存执行的sql语句
     */
    private $_sql;

    /**
     * 保存的一个PDOstatement实例
     */
    private $_stm;

    /**
     * pdo链接对象
     */
    private $_db;

    private $dbname;

    public function __construct($config)
    {
        if ($config != null) {
            try {
                $options = array();
                if(!empty($config['charset'])) {
                    $this->_charset = $config['charset'];
                }
                $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $this->_charset;
                $this->_db = new \PDO($config['dsn'], $config['user'], $config['psw'], $options);
                $arr = explode('dbname=', $config['dsn']);
                $name = explode(';', $arr[1]);
                $this->dbname = $name[0];
            } catch (\PDOException $e) {
                //返回客户端错误信息
                throw new \PDOException($e);
            }
        } else {
            trigger_error('配置参数不能为空', E_USER_WARNING);
        }
        $this->_init();
    }

    /**
     * 设置mysql连接相关参数，比如超时时间、返回数据类型、字符集等
     */
    private function _init()
    {
        $this->_db->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $this->_db->setAttribute(\PDO::ATTR_TIMEOUT, $this->_timeout);
        $this->_db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->_db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * 设置连接字符集
     *
     * @params string $charset 字符集
     * @return void
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
        $this->_db->query('set names ' . $this->_charset);
    }

    /**
     * 编译sql语句，并绑定参数，返回执行结果
     *
     * $param string $sql
     * @param array  $params 绑定参数
     *
     * @return mixed
     */
    private function _exec($sql, $param)
    {
        $stime = getMicrotime();
        $this->_sql = $sql;
        $result = false;
        try {
            $this->_sth = $this->_db->prepare($this->_sql);
            if (!empty($param)) {
                foreach ($param as $k => $v) {
                    $this->_sth->bindValue(':' . $v[0], $v[1], $v[2]);
                }
            }
            $this->_sth->execute();
            $result = $this->_sth->rowCount() > 0 ? $this->_sth->rowCount() : -1;
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $exSql = $this->getExecuteSql($sql, $param);
            $this->_sqlLog($stime, $etime, $exSql, $result);
            throw $e;
        }
        $etime = getMicrotime();
        $exSql = $this->getExecuteSql($sql, $param);
        $this->_sqlLog($stime, $etime, $exSql, $result);

        return $result;
    }

    /**
     * 封装修改、删除，非id操作
     *
     * @param string $sql
     * @param array $param
     * @return booler
     */
    public function ud($sql, $param)
    {
        return $this->_exec($sql, $param);
    }

    /**
     * 封装一个查询
     *
     * @param string $sql
     * @param array $param
     * @return array
     */
    public function g($sql, $params)
    {
        //$stime=getMicrotime();
        if ($this->_exec($sql, $params)) {
            //   $etime=getMicrotime();
            //   $exSql=$this->getExecuteSql($sql,$params);
            //   $this->_sqlLog($stime,$etime,$exSql);
            return $this->_sth->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function getExecuteSql($sql, $params)
    {
        $executeSql = $sql;
        //echo $executeSql;
        foreach ($params as $param) {
            //var_dump($param);
            if ($param[2] == 1) {
                $executeSql = str_replace(":$param[0]", $param[1], $executeSql);
            } else {
                $executeSql = str_replace(":$param[0]", "'$param[1]'", $executeSql);
            }
        }
        //echo $executeSql;
        return $executeSql;
    }

    /**
     * 插入一条记录 若$sid有值则在总表和分表都插入数据
     *
     * @param string $tbl 数据库表，如果附带数据库，用 db.tbl格式
     * @param array $fields 插入的数据库字段
     * @return false:int 操作成功返回记录影响的行数，否则返回false
     */
    public function c($tbl, $fields)
    {
        return $this->_insert($tbl, $fields);
    }

    /**
     * @abstract 对c,u,d简单的参数绑定操作封装
     * @params array $data 需要绑定的字段值
     * @return void
     */
    private function _insert($tbl, $fields)
    {
        $_fields = array_keys($fields);
        $_vals = array_values($fields);
        $_vals = implode(', ', $_vals);
        $length = count($fields);
        $this->_sql = 'INSERT INTO ' . $tbl . ' (`' . implode('`,`', $_fields) . '`) ';
        $this->_sql .= 'VALUES (?' . str_repeat(', ?', $length - 1) . ')';

        $sql = 'INSERT INTO ' . $tbl . ' (`' . implode('`,`', $_fields) . '`) ';
        $sql .= 'VALUES (' . $_vals . ')';
        $stime = getMicrotime();
        $this->_stm = $this->_db->prepare($this->_sql);
        $result = false;
        try {
            if ($this->_stm) {
                $result = $this->_stm->execute(array_values($fields));
            }
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $this->_sqlLog($stime, $etime, $sql, $result);
            throw $e;
        }
        $etime = getMicrotime();
        $this->_sqlLog($stime, $etime, $sql, $result);

        return $result;
    }

    /**
     * 使用pdo插入多条记录
     *
     * @param type $tbl
     * @param type $fields
     * @return type
     */
    public function cs($tbl, $fields)
    {

        $fields = array_values($fields);

        if (count($fields) == 1) {
            return $this->_insert($tbl, $fields[0]);
        }

        $stime = getMicrotime();

        $_fields = array_keys($fields[0]);
        $sql = 'INSERT INTO `' . $tbl . '` (`' . implode('`,`', $_fields) . '`) VALUES';

        foreach ($fields as $key => $arr) {
            $num = count($arr);
            $i = 0;

            $data[$key] = '(';
            foreach ($arr as $_k => $_v) {
                $i ++;
                $_v = addslashes($_v);
                if ($i == $num) {
                    $data[$key] .= "'{$_v}')";
                } else {
                    $data[$key] .= "'{$_v}', ";
                }
            }
        }

        $sql = $sql . implode(',', $data);
        $result = false;
        try {
            $result = $this->_db->exec($sql);
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $this->_sqlLog($stime, $etime, $sql, $result);
            throw $e;
        }

        $etime = getMicrotime();
        $this->_sqlLog($stime, $etime, $sql, $result);

        return $result;
    }

    /**
     * 记录mysql执行明细
     *
     * @param float $stime 开始时间
     * @param float $etime 结束时间
     */
    private function _sqlLog($stime, $etime, $sql = '', $result)
    {
        if (Core::$conf['pdo'] == 1) {
            $time = $etime - $stime;
            $time = sprintf("%.3f", $time * 1000);

            if (is_object($result)) {
                $result = array();
            }

            Core::$time['mysql']['details'][] = array(
                'sql' => $sql,
                'time' => $time,
                'result' => $result,
            );
            Core::$time['mysql']['time'] += $time;
            Core::$time['mysql']['cnt'] ++;

            //将执行时间超过sqlTime的语句记录到日志中
            $sqlTime = \DevSxe\Lib\R('\DevSxe\Application\Config\SqlTime');
            //当sqlTime未配置时，设置sqlTime为1000毫秒
            if (empty($sqlTime['sqlTime']) || $sqlTime['sqlTime'] < 0) {
                $sqlTime['sqlTime'] = 1000;
            }
            if ($time > $sqlTime['sqlTime'] && class_exists('\DevSxe\Service\Log\DevSxeLog')) {
                $delimiter = chr(30) . ' ';
                $mes = '[sql] => ' . str_replace(array("\r", "\n"), ' ', $sql) . 
                    $delimiter . '[time] => ' . $time;
                \DevSxe\Service\Log\DevSxeLog::warning($mes, __FILE__, __LINE__);
            }
            //if (class_exists('\DevSxe\Service\Log\DevSxeLog') && isset($sqlTime['log']) && $sqlTime['log'] === true) {
            //    \DevSxe\Service\Log\DevSxeLog::config([
            //        'yewu' => 'sql',
            //    ]);
            //    $delimiter = chr(30) . ' ';
            //    $mes = '[' . $this->dbname . ']' . $delimiter . '[' . $time . ']' . $delimiter .
            //        str_replace(array("\r", "\n"), ' ', $sql);
            //    \DevSxe\Service\Log\DevSxeLog::warning($mes, __FILE__, __LINE__);
            //}
        }
    }

    public function query($sql)
    {
        $stime = getMicrotime();
        $result = false;
        try {
            $result = $this->_db->query($sql);
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $this->_sqlLog($stime, $etime, $sql, $result);
            throw $e;
        }
        $etime = getMicrotime();
        $this->_sqlLog($stime, $etime, $sql, $result);
        return $result;
    }

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        $stime = getMicrotime();
        $this->_db->beginTransaction();
        $etime = getMicrotime();
        if (Core::$conf['pdo'] == 1) {
            $time = $etime - $stime;
            $time = (int) ($time * 1000000);
            Core::$time['mysql']['details'][] = array(
                'sql' => 'begin transaction',
                'time' => $time,
            );
            Core::$time['mysql']['time'] += $time;
            Core::$time['mysql']['cnt'] ++;
        }
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $stime = getMicrotime();
        $this->_db->rollback();
        $etime = getMicrotime();
        if (Core::$conf['pdo'] == 1) {
            $time = $etime - $stime;
            $time = (int) ($time * 1000000);
            Core::$time['mysql']['details'][] = array(
                'sql' => 'rollback transaction',
                'time' => $time,
            );
            Core::$time['mysql']['time'] += $time;
            Core::$time['mysql']['cnt'] ++;
        }
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $stime = getMicrotime();
        $this->_db->commit();
        $etime = getMicrotime();
        if (Core::$conf['pdo'] == 1) {
            $time = $etime - $stime;
            $time = (int) ($time * 1000000);
            Core::$time['mysql']['details'][] = array(
                'sql' => 'commit transaction',
                'time' => $time,
            );
            Core::$time['mysql']['time'] += $time;
            Core::$time['mysql']['cnt'] ++;
        }
    }

    /**
     * 删除数据(无法执行）
     *
     * @params int $id 主键id
     * @return booler
     */
    public function d($tbl, $id)
    {
        $stime = getMicrotime();
        $this->_sql = 'DELETE FROM ' . $tbl . ' WHERE id = ?';
        $sql = str_replace('?', $id, $this->_sql);
        $result = false;
        try {
            $this->_sth = $this->_db->prepare($this->_sql);
            $result = $this->_sth->execute(array($id));
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $this->_sqlLog($stime, $etime, $sql, $result);
            throw $e;
        }
        $etime = getMicrotime();
        $this->_sqlLog($stime, $etime, $sql, $result);
        return $result; //$this->_execute(array($id));
    }

    /**
     * 修改数据
     *
     * @params array $fields 修改的字段
     * @return bool 操作成功返回TRUE，否则返回FALSE
     */
    public function u($tbl, $fields, $id, $where = 'id')
    {
        $this->_sql = 'UPDATE `' . $tbl . '` SET ';
        $sql = $this->_sql;
        foreach ($fields as $k => $v) {
            $this->_sql .= '`' . $k . '` = ?,';
            $sql .='`' . $k . '` = ' . $v . ',';
        }
        $this->_sql = trim($this->_sql, ',');
        $sql = trim($sql, ',');
        $this->_sql .= ' WHERE `' . $where . '` = ?';
        $sql .=' WHERE `' . $where . '` = ' . $id;
        $fields[$where] = $id;

        $result = false;
        $stime = getMicrotime();
        try {
            $this->_sth = $this->_db->prepare($this->_sql);
            $result = $this->_sth->execute(array_values($fields));
        } catch (\PDOException $e) {
            $etime = getMicrotime();
            $this->_sqlLog($stime, $etime, $sql, $result);
            throw $e;
        }
        $etime = getMicrotime();
        $this->_sqlLog($stime, $etime, $sql, $result);
        return $result;
    }

    /**
     * 返回当前unix时间戳和微秒数
     *
     * @params void
     * return floa 微妙数
     */
    function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * 获取lastid
     */
    public function lastInsertId()
    {
        return $this->_db->lastInsertId();
    }

    /**
     * 设置链接属性
     *
     * @params array $config
     * return void
     */
    public function setAttr($config)
    {
        foreach ($config as $k => $val) {
            $this->_db->setAttribute($k, $val);
        }
    }

    /**
     * 获取一个PDOStatement对象
     *
     * @params void
     * @return PODStatement
     */
    public function getPDOStatement($sql, $params)
    {
        if ($this->_exec($sql, $params)) {
            return $this->_sth;
        }
    }

}
