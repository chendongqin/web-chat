<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/12
 * Time: 12:27
 */

namespace lib\db;
class sqli
{
    private $link = null;
    private $_config = [];
    private $_prefix = '';

    public function __construct($config = [])
    {
        $this->setDbConfig();
        if ($config) {
            $this->_config = array_merge($this->_config, $config);
        }
        $this->connect();
        $this->_prefix = $this->_config['prefix'];
    }

    /**
     * 加载数据库配置
     * @return $this
     */
    public function setDbConfig()
    {
        $this->_config = include ROOT_PATH . '/conf/dbconfig.php';
        return $this;
    }

    /**连接数据库
     * @param array $config
     * @return \mysqli|null
     */
    public function connect($config = [])
    {
        if ($config) {
            $this->_config = array_merge($this->_config, $config);
        }
        if ($this->link === null) {
            $link = new \mysqli($this->_config['host'], $this->_config['user'], $this->_config['password'], $this->_config['db_name'], $this->_config['port'], $this->_config['socket']);
            if ($link->connect_errno) {
                die('数据库连接错误：' . $link->connect_errno);
            }
            $link->query('set names ' . $this->_config['charset'] . ';');
            $this->link = $link;
        }
        return $this->link;
    }

    /**
     * 插入数据
     * @param $table
     * @param $data
     * @return bool|int|string
     */
    public function insert($table, $data)
    {
        $table = $this->trueTable($table);
        if (is_object($data)) {
            $json = json_encode($data);
            $data = json_encode($json, true);
        } elseif (is_string($data)) {
            throw new \Error('插入的参数是一个对象或数组');
        }
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', array_keys($data)) . "`) VALUES('" . implode("','", $data) . "');";
        $result = $this->link->query($sql);
        if ($result === false) {
            return false;
        }
        $insertId = mysqli_insert_id($this->link);
        return $insertId;
    }

    /**
     * 插入多条数据
     * @param $table
     * @param array $data
     * @return bool|int|string
     */
    public function insertAll($table, array $data)
    {
        if (count($data) > 500) {
            throw new \Error('数据溢出，批量插入不大于500条');
        }
        $table = $this->trueTable($table);
        if (is_string($data)) {
            throw new \Error('插入的参数是一个对象或数组');
        }
        $values = '';
        $columns = [];
        foreach ($data as $item) {
            if (is_object($item)) {
                $json = json_encode($item);
                $item = json_encode($json, true);
            } elseif (is_string($item)) {
                throw new \Error('插入的参数是一个对象或数组');
            }
            if (empty($columns)) {
                $columns = array_keys($item);
            } elseif ($columns != array_keys($item)) {
                throw new \Error('批量插入的数据不一致');
            }
            $values .= "('" . implode("','", $item) . "'),";
        }
        $values = rtrim($values, ',');
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES ' . $values . ';';
        try {
            $result = $this->link->query($sql);
            if ($result === false) {
                return false;
            }
            $affectedRows = mysqli_affected_rows($this->link);
            return $affectedRows;
        } catch (\Error $error) {
            throw new \Error('插入数据有误');
        }
    }

    /**
     * 更新数据
     * @param $table
     * @param array $data
     * @param null $where
     * @return mixed
     */
    public function update($table, array $data, $where = null)
    {
        $table = $this->trueTable($table);
        if (isset($data['id'])) {
            $where['id'] = $data['id'];
            unset($data['id']);
        }
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $setStr = '';
        foreach ($data as $key => $value) {
            $setStr .= ' `' . $key . "`='" . $value . "',";
        }
        $setStr = rtrim($setStr, ',');
        $sql = 'UPDATE `' . $table . '` SET ' . $setStr . ' ' . $whereStr;
        $result = $this->link->query($sql);
        if ($result === false) {
            return false;
        }
        $affectedRows = mysqli_affected_rows($this->link);
        return $affectedRows;
    }

    /**
     * 删除
     * @param $table
     * @param $where
     * @return bool|int
     */
    public function delete($table, $where)
    {
        $columns = $this->columns($table);
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        //是否存在软删除字段is_del，否则进行真删除
        if (isset($columns['is_del'])) {
            $sql = 'UPDATE `' . $table . '` SET `is_del`=1 ' . $whereStr;
        } else {
            $sql = 'DELETE FROM `' . $table . '` ' . $whereStr;
        }
        $result = $this->link->query($sql);
        if ($result === false) {
            return false;
        }
        $affectedRows = mysqli_affected_rows($this->link);
        return $affectedRows;
    }

    /**
     * 查询返回结果集
     * @param $table
     * @param null $where //查询条件
     * @param null $order //排序
     * @param int $page //分页处理
     * @param int $limit //每页条数
     * @param int $count //总条数
     * @param null $field //查询字段
     * @return array //查询结果集，数组返回
     */
    public function select($table, $where = null, $order = null, $page = 0, $limit = 0, &$count = 0, $field = null)
    {
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $byOrder = $this->byOrder($order);
        $limitStr = $this->limit($page, $limit);
        $fieldStr = $this->field($field);
        try {
            $countSql = 'SELECT COUNT(1) FROM `' . $table .'`'. $whereStr . ' ' . $byOrder;
            $result = $this->link->query($countSql);
            $count = $result->num_rows;
            $result->close();
            $rows = [];
            if ($count > 0) {
                $sql = 'SELECT ' . $fieldStr . ' FROM `' . $table .'`'. $whereStr . ' ' . $byOrder . ' ' . $limitStr . ';';
                $result = $this->link->query($sql);
                while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                    $rows[] = $row;
                }
                $result->close();
            }
            return $rows;
        } catch (\Error $error) {
            throw new \Error('sql错误', 500);
        }
    }

    /**
     * 分组查询
     * @param $table
     * @param null $where
     * @param null $order
     * @param null $field
     * @param null $group
     * @param null $having
     * @return array
     */
    public function selectOfGroup($table, $where = null, $order = null, $field = null, $group = null, $having = null)
    {
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $byOrder = $this->byOrder($order);
        $fieldStr = $this->field($field);
        $groupStr = $this->byGroup($group);
        $havingStr = $this->where($having);
        $havingStr = empty($havingStr) ? '' : ' HAVING ' . $havingStr;
        try {
            $rows = [];
            $sql = 'SELECT ' . $fieldStr . ' FROM `' . $table.'`' . $whereStr . ' ' . $groupStr . ' ' . $havingStr . ' ' . $byOrder . ';';
            $result = $this->link->query($sql);
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $rows[] = $row;
            }
            $result->close();
            return $rows;
        } catch (\Error $error) {
            throw new \Error('sql错误', 500);
        }
    }

    /**
     * 统计
     * @param $table
     * @param null $where
     * @return int
     */
    public function count($table, $where = null)
    {
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $sql = 'SELECT COUNT(1) AS total FROM `' . $table . '` ' . $whereStr . ';';
        $result = $this->link->query($sql);
        $row = $result->fetch_array(MYSQLI_ASSOC);
        return (int)$row['total'];
    }

    /**
     * 求和
     * @param $table
     * @param $field
     * @param null $where
     * @return float
     */
    public function sum($table, $field, $where = null)
    {
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $sql = 'SELECT SUM(' . $field . ') AS total FROM `' . $table . '` ' . $whereStr . ';';
        $result = $this->link->query($sql);
        $row = $result->fetch_array(MYSQLI_ASSOC);
        return (float)$row['total'];
    }

    /**
     * 查询一条语句
     * @param $table
     * @param null $where
     * @param null $order
     * @param null $field
     * @return mixed
     */
    public function find($table, $where = null, $order = null, $field = null)
    {
        $table = $this->trueTable($table);
        $whereStr = $this->where($where);
        $whereStr = empty($whereStr) ? '' : ' WHERE ' . $whereStr;
        $byOrder = $this->byOrder($order);
        $fieldStr = $this->field($field);
        try {
            $sql = 'SELECT ' . $fieldStr . ' FROM `' . $table .'`'. $whereStr . ' ' . $byOrder . ' LIMIT 1;';
            $result = $this->link->query($sql);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $result->close();
            return $row;
        } catch (\Error $error) {
            throw new \Error('sql错误', 500);
        }
    }


    /**
     *分组转sql
     * @param null $group
     * @return string
     */
    public function byGroup($group = null)
    {
        if (empty($group)) {
            return '';
        }
        $str = 'GROUP BY ';
        if (is_array($group)) {
            foreach ($group as $value) {
                $str .= $value . ',';
            }
            $str = rtrim($str, ',');
        } elseif (is_string($group)) {
            $str .= $group;
        }
        return $str;
    }

    /**
     * 返回字段转sql
     * @param null $field
     * @return string
     */
    public function field($field = null)
    {
        $str = '*';
        if (!empty($field)) {
            if (is_string($field)) {
                $str = $field;
            } elseif (is_array($field)) {
                $str = ' ' . implode(',', $field) . ' ';
            }
        }
        return ' ' . $str . ' ';
    }

    /**
     * 分页转sql
     * @param int $page
     * @param int $limit
     * @return string
     */
    public function limit($page = 0, $limit = 0)
    {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($limit == 0) {
            return '';
        }
        $begin = $page * $limit;
        $str = ' LIMIT ' . $begin . ',' . $limit . ' ';
        return $str;
    }

    /**
     * 排序转sql
     * @param null $order
     * @return string
     */
    public function byOrder($order = null)
    {
        if (empty($order)) {
            return '';
        }
        $str = 'ORDER BY ';
        if (is_array($order)) {
            foreach ($order as $key => $value) {
                if (is_numeric($key)) {
                    $str .= $value . ',';
                } else {
                    $str .= $key . ' ' . strtoupper($value) . ',';
                }
            }
            $str = rtrim($str, ',');
        } elseif (is_string($order)) {
            $str .= $order;
        }
        return $str;
    }

    /**
     * 查询条件转sql
     * @param null $where
     * @return string
     */
    public function where($where = null)
    {
        if (empty($where)) {
            return '';
        }
        $whereStr = '';
        $whereOr = [];
        if (isset($where['OR:'])) {
            $whereOr = $where['OR:'];
            unset($where['OR:']);
        }
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_numeric($key)) {
                    if (is_array($value)) {
                        throw new \Error('where is not array', 500);
                    }
                    $whereStr .= addslashes($value) . ' AND ';
                } elseif (strtoupper($key) == 'LIKE:') {
                    if (!is_array($value)) {
                        throw new \Error('LIKE value is array', 500);
                    }
                    $whereStr .= $this->likeSql($value) . ' AND ';
                } else {
                    if (is_array($value)) {
                        $whereStr .= '`' . $key . '` IN (' . implode(',', $value) . ') AND ';
                    } else {
                        $whereStr .= '`' . $key . '`' . "='" . addslashes($value) . "' AND ";
                    }
                }
            }
        } elseif (is_string($where)) {
            $whereStr = $where;
        }
        $whereStr = rtrim($whereStr, 'AND ');
        if ($whereOr) {
            $whereOrStr = '';
            if (is_array($whereOr)) {
                foreach ($whereOr as $key => $value) {
                    if (is_numeric($key)) {
                        if (is_array($value)) {
                            throw new \Error('where is not array', 500);
                        }
                        $whereOrStr .= addslashes($value) . ' AND ';
                    } else {
                        if (is_array($value)) {
                            $whereOrStr .= '`' . $key . '` IN (' . implode(',', $value) . ') AND ';
                        } else {
                            $whereOrStr .= '`' . $key . '`' . "='" . addslashes($value) . "' AND ";
                        }
                    }
                }
            } elseif (is_string($whereOr)) {
                $whereOrStr = $where;
            }
            $whereOrStr = rtrim($whereOrStr, 'AND ');
            $whereStr = '(' . $whereStr . ') OR (' . $whereOrStr . ')';
        }
        return $whereStr;
    }


    /**
     * like转sql,参数为数组，第一个参数为字段名，第二个参数为模糊查询值，第三个参数为模糊查询类型
     * @param array $like
     * @return string
     */
    public function likeSql(array $like)
    {
        if (count($like) < 2) {
            throw new \Error('LIKE：需要2个以上参数');
        }
        $type = isset($like[2]) ? $like[2] : 'both';
        if (strtolower($type) == 'left') {
            $str = ' ' . $like[0] . " LIKE '" . $like[1] . "%' ";
        } elseif (strtolower($type) == 'right') {
            $str = ' ' . $like[0] . " LIKE '%" . $like[1] . "'";
        } else {
            $str = ' ' . $like[0] . " LIKE '%" . $like[1] . "%' ";
        }
        return $str;
    }

    /**
     * 获取表结构
     * @param $table
     * @return array
     */
    public function columns($table)
    {
        $table = $this->trueTable($table);
        $result = $this->link->query('SHOW COLUMNS FROM `' . $table . '`');
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[$row['Field']] = $row;
        }
        return $rows;
    }

    /**
     * 获取真实的表名
     * @param $table
     * @return string
     */
    public function trueTable($table)
    {
        return $this->_prefix . $table;
    }


    /**
     * 关闭连接
     * @return bool
     */
    public function close()
    {
        if (!$this->link) {
            $this->link->close();
        }
        return true;
    }

    public function __destruct()
    {
        $this->close();
    }
}