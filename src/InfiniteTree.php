<?php
/**
 * Created by PhpStorm.
 * Author: weiyongqiang <hayixia606@163.com>
 * Date: 2019-06-04
 * Time: 18:08
 */

namespace gmars\infinitetree;


class InfiniteTree
{
    /**
     * 表名
     * @var string
     */
    private $_tableName = "tree";

    /**
     * 左键
     * @var string
     */
    private $_leftKey = "left_key";

    /**
     * 右键
     * @var string
     */
    private $_rightKey = "right_key";

    /**
     * 父键
     * @var string
     */
    private $_parentKey = "parent_id";

    /**
     * 层级键
     * @var string
     */
    private $_levelKey = "level";

    /**
     * 主键
     * @var string
     */
    private $_primaryKey = "id";

    /**
     * 数据库连接
     * @var \mysqli
     */
    private $_db;

    /**
     * InfiniteTree constructor.
     * InfiniteTree constructor.
     * @param string $tableName
     * @param array $dbConfig
     * @param array $keyConfig
     * @throws \Exception
     */
    public function __construct($tableName = "", $dbConfig = [], $keyConfig = [])
    {
        if (!extension_loaded('mysqli')) {
            throw new \Exception('未找到您的mysqli扩展');
        }

        if (!empty($tableName)) {
            $this->_tableName = $tableName;
        }

        if (!empty($keyConfig) && is_array($keyConfig)) {
            $this->setKeyConfig($keyConfig);
        }

        list(
            'hostname' => $host,
            'username' => $userName,
            'password' => $password,
            'database' => $dataBase,
            'hostport' => $port
            ) = $dbConfig;
        try {
            $this->_db = mysqli_connect($host, $userName, $password, $dataBase, $port);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    

    /**
     * 配置对应的键
     * @param array $config
     */
    public function setKeyConfig($config = [])
    {
        if (is_array($config)) {
            foreach ($config as $k => $v)
            {
                $keyArr = array_filter(explode('_', $k));
                if (count($keyArr) < 2) {
                    continue;
                }
                $property = '_' . $keyArr[0] . strtoupper($keyArr[1]{0}) . substr($keyArr[1], 1);
                if (property_exists(self::class, $property)) {
                    $this->$property = $v;
                }
            }
        }
    }

    /**
     * mysql query
     * @param $sql
     * @return bool|\mysqli_result
     */
    private function _query($sql)
    {
        return mysqli_query($this->_db, $sql);
    }

    /**
     * 反馈错误消息
     * @return string
     */
    private function _error()
    {
        return mysqli_error($this->_db);
    }

    /**
     * mysql select
     * @param string $condition
     * @param string $orderby
     * @return array
     */
    private function _select($condition = "1=1", $orderby = "")
    {
        $sql = "SELECT * FROM $this->_tableName WHERE $condition ";
        if (!empty($orderby)) {
            $sql .= "ORDER BY $orderby";
        }
        $result = $this->_query($sql);
        if (empty($result)) {
            return [];
        }
        $row = [];
        while ($assocRow = $result->fetch_assoc()) {
            $row[] = $assocRow;
        }
        return $row;
    }

    /**
     * mysql max field
     * @param $key
     * @return mixed
     */
    private function _getMaxField($key)
    {
        $sql = "SELECT MAX($key) FROM $this->_tableName";
        return $this->_query($sql)->fetch_row();
    }

    /**
     * 查询表的所有字段
     * @return array
     */
    private function _getFields()
    {
        $fieldSql = "SHOW FIELDS FROM $this->_tableName";
        $res = $this->_query($fieldSql);
        $result = [];
        while ($row = $res->fetch_row())
        {
            $result[] = $row[0];
        }
        return $result;
    }

    /**
     * 插入数据
     * @param $data
     * @return bool
     * @throws \Exception
     */
    private function _insert($data)
    {
        $fields = $this->_getFields();
        $field = [];
        $value = [];
        foreach ($data as $k => $v)
        {
            if (in_array($k, $fields)) {
                $field[] = "`$k`";
                $value[] = "'$v'";
            }
        }
        $fieldStr = implode(',', $field);
        $valueStr = implode(',', $value);
        if (empty($field)) {
            throw new \Exception('插入数据不能为空');
        }
        $sql = "INSERT INTO $this->_tableName ($fieldStr) VALUES ($valueStr)";
        if ($this->_query($sql) === false) {
            throw new \Exception($this->_error());
        }
        return true;
    }

    /**
     * 检查数据表是否存在，不存在则自动创建
     * @return bool
     * @throws \Exception
     */
    public function checkTable()
    {
        $sql = "SHOW TABLES LIKE '$this->_tableName'";
        if (!$this->_query($sql)->fetch_assoc()) {
            $createSql = "CREATE TABLE `{$this->_tableName}` (
                          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                          `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
                          `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父节点id',
                          `left_key` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '节点左值',
                          `right_key` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '节点右值',
                          `level` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '节点层级',
                          PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            if ($this->_query($createSql) === true) {
                return true;
            }
            throw new \Exception($this->_error());
        } else {
            return true;
        }
    }

    /**
     * 获取整棵树并且按照左值排序
     * @return array
     */
    public function getTree()
    {
        return $this->_select("1=1", "$this->_leftKey ASC");
    }

    /**
     * 获取某个节点的分支
     * @param $id
     * @param string $operationOne
     * @param string $operationTwo
     * @return array
     * @throws \Exception
     */
    public function getBranch($id, $operationOne = '>', $operationTwo = '<')
    {
        $item = $this->getItem($id);
        if (empty($item)) {
            throw new \Exception('未查询到该节点');
        }
        $where = "$this->_leftKey $operationOne {$item[$this->_leftKey]}";
        $where .= " AND $this->_rightKey $operationTwo {$item[$this->_rightKey]}";
        return $this->_select($where, "$this->_leftKey ASC");
    }

    /**
     * 获取当前分支包含当前节点
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function getPath($id)
    {
        return $this->getBranch($id, '>=', '<=');
    }

    /**
     * 获取当前节点的子节点，注意不包含孙节点
     * @param $id
     * @return array
     */
    public function getChildren($id)
    {
        $where = "$this->_parentKey = $id";
        return $this->_select($where);
    }

    /**
     * 插入新节点
     * @param $parentId
     * @param array $data
     * @param string $position
     * @return bool
     * @throws \Exception
     */
    public function insert($parentId, $data = [], $position = "top")
    {
        $parent = $this->getItem($parentId);
        if (empty($parent)) {
            $level = 1;
            $key = $position == "top" ? 1 : $this->_getMaxField($this->_rightKey);
        } else {
            $key = $position == "top" ? $parent[$this->_leftKey] + 1 : $parent[$this->_rightKey];
            $level = $parent[$this->_levelKey] + 1;
        }
        $sql = "UPDATE {$this->_tableName} SET {$this->_rightKey} = {$this->_rightKey}+2,{$this->_leftKey} = IF({$this->_leftKey}>={$key},{$this->_leftKey}+2,{$this->_leftKey}) WHERE {$this->_rightKey}>={$key}";
        $this->_db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        if ($this->_query($sql) === false) {
            $this->_db->rollback();
            throw new \Exception($this->_error());
        }
        $node = [
            $this->_parentKey => $parentId,
            $this->_leftKey => $key,
            $this->_rightKey => $key + 1,
            $this->_levelKey => $level
        ];
        $tmpData = array_merge($node, $data);
        //查询键
        if ($this->_insert($tmpData) === true) {
            $this->_db->commit();
            return true;
        }
        return false;
    }

    /**
     * 删除节点
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function delete($id)
    {
        $item = $this->getItem($id);
        if (empty($item)) {
            throw new \Exception('未查询到节点信息');
        }
        $branchWidth = $item[$this->_rightKey] - $item[$this->_leftKey] + 1;
        $delSql = "DELETE FROM $this->_tableName WHERE $this->_leftKey >= {$item[$this->_leftKey]} AND $this->_rightKey <= {$item[$this->_rightKey]}";
        $updateSql = "UPDATE {$this->_tableName} SET {$this->_leftKey} = IF({$this->_leftKey}>{$item[$this->_leftKey]}, {$this->_leftKey}-{$branchWidth}, {$this->_leftKey}), {$this->_rightKey} = {$this->_rightKey}-{$branchWidth} WHERE {$this->_rightKey}>{$item[$this->_rightKey]}";
        $this->_db->begin_transaction(MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
        if ($this->_query($delSql) === false) {
            $this->_db->rollback();
            throw new \Exception($this->_error());
        }
        if ($this->_query($updateSql) === false) {
            $this->_db->rollback();
            throw new \Exception($this->_error());
        }
        $this->_db->commit();
        return true;
    }

    /**
     * 把某个节点移动到另一个节点下
     * @param $id
     * @param $parentId
     * @param string $position
     * @return bool
     * @throws \Exception
     */
    public function moveUnder($id, $parentId, $position = "bottom")
    {
        $item = $this->getItem($id);
        if (empty($item)) {
            throw new \Exception('未查询到节点信息');
        }
        $parent = $this->getItem($parentId);
        if (empty($parent)) {
            $level = 1;
            $nearKey = $position == "top" ? 0 : $this->_getMaxField($this->_rightKey);
        } else {
            $level = $parent[$this->_levelKey] + 1;
            $nearKey = $position == "top" ? $parent[$this->_leftKey] : $parent[$this->_rightKey] - 1;
        }
        return $this->_move($id, $parentId, $nearKey, $level);
    }

    /**
     * 移动某个节点到另一个节点的左或者右
     * @param $id
     * @param $nearId
     * @param string $position
     * @return bool
     * @throws \Exception
     */
    public function moveNear($id, $nearId, $position = "after")
    {
        $item = $this->getItem($id);
        if (empty($item)) {
            throw new \Exception('未查询到节点信息');
        }
        $near = $this->getItem($nearId);
        if (empty($near)) {
            throw new \Exception('移动的基准节点不存在不可移动');
        }
        $level = $near[$this->_levelKey];
        $nearKey = $position == "before" ? $near[$this->_leftKey] - 1 : $near[$this->_rightKey];
        return $this->_move($id, $near[$this->_parentKey], $nearKey, $level);
    }

    /**
     * 移动节点
     * @param $id
     * @param $parentId
     * @param $nearKey
     * @param $level
     * @return bool
     * @throws \Exception
     */
    private function _move($id, $parentId, $nearKey, $level)
    {
        $item = $this->getItem($id);
        if (empty($id)) {
            throw new \Exception('未查询到节点信息');
        }
        if ($nearKey >= $item[$this->_leftKey] && $nearKey <= $item[$this->_rightKey]) {
            throw new \Exception('移动区间错误，请检查后再试');
        }
        $keyWidth = $item[$this->_rightKey] - $item[$this->_leftKey] + 1;
        $levelWidth = $level - $item[$this->_levelKey];

        if ($item[$this->_rightKey] < $nearKey) {
            $treeEdit = $nearKey - $item[$this->_leftKey] + 1 - $keyWidth;
            $sql = "UPDATE {$this->_tableName} 
                    SET 
                    {$this->_leftKey} = IF(
                        {$this->_rightKey} <= {$item[$this->_rightKey]},
                        {$this->_leftKey} + {$treeEdit},
                        IF(
                            {$this->_leftKey} > {$item[$this->_rightKey]},
                            {$this->_leftKey} - {$keyWidth},
                            {$this->_leftKey}
                        )
                    ),
                    {$this->_levelKey} = IF(
                        {$this->_rightKey} <= {$item[$this->_rightKey]},
                        {$this->_levelKey} + {$levelWidth},
                        {$this->_levelKey}
                    ),
                    {$this->_rightKey} = IF(
                        {$this->_rightKey} <= {$item[$this->_rightKey]},
                        {$this->_rightKey} + {$treeEdit},
                        IF(
                            {$this->_rightKey} <= {$nearKey},
                            {$this->_rightKey} - {$keyWidth},
                            {$this->_rightKey}
                        )
                    ),
                    {$this->_parentKey} = IF(
                        {$this->_primaryKey} = {$id},
                        {$parentId},
                        {$this->_parentKey}
                    )
                    WHERE 
                    {$this->_rightKey} > {$item[$this->_leftKey]}
                    AND 
                    {$this->_leftKey} <= {$nearKey}";
        } else {
            $treeEdit = $nearKey - $item[$this->_leftKey]+1;
            $sql = "UPDATE {$this->_tableName}
                    SET 
                    {$this->_rightKey} = IF(
						{$this->_leftKey} >= {$item[$this->_leftKey]},
						{$this->_rightKey} + {$treeEdit},
						IF(
							{$this->_rightKey} < {$item[$this->_leftKey]},
							{$this->_rightKey} + {$keyWidth},
							{$this->_rightKey}
						)
					),
					{$this->_levelKey} = IF(
						{$this->_leftKey} >= {$item[$this->_leftKey]},
						{$this->_levelKey} + {$levelWidth},
						{$this->_levelKey}
					),
					{$this->_leftKey} = IF(
						{$this->_leftKey} >= {$item[$this->_leftKey]},
						{$this->_leftKey} + {$treeEdit},
						IF(
							{$this->_leftKey} > {$nearKey},
							{$this->_leftKey} + {$keyWidth},
							{$this->_leftKey}
						)
					),
					{$this->_parentKey} = IF(
						{$this->_primaryKey} = {$id},
						{$parentId},
						{$this->_parentKey}
					)
					WHERE
					{$this->_rightKey} > {$nearKey}
					AND
					{$this->_leftKey} < {$item[$this->_rightKey]}";
        }
        if ($this->_query($sql) === false) {
            throw new \Exception($this->_error());
        }
        return true;
    }

    /**
     * 获取当前节点
     * @param $id
     * @return array|mixed
     */
    public function getItem($id)
    {
        $condition = "$this->_primaryKey = $id";
        $res = $this->_select($condition);
        if (!empty($res)) {
            return $res[0];
        }
        return [];
    }
}