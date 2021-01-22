<?php
/*
注意细节:实例化对象时传入的是数据库的路径，要是数据库不存在的话会自动创建。
*/
class sqlLite extends SQLite3
{
    public $url;

    /**
     * SqlLite 数据库路径设置
     */
    function __construct($url)
    {
        $this->url = $url;
        $this->open($url);
    }

    function check_input($value)
    {
        if (get_magic_quotes_gpc()) {
            $value = sqlite_escape_string($value);
        }
        return $value;
    }

    /**
     * SqlLite 创建数据库
     *
     * create("test", 
     *		array("id"=>"integer PRIMARY KEY autoincrement", 
     *			"name"=>"VARCHAR(50)", 
     *			"age"=>"INT  NOT NULL",
     *			"article"=>"TEXT    NOT NULL")
     *	);
     *
     *	$name Array 请传递数组
     *	$type String 请传递|分割的字符串
     */
    function create($table, $name, $type = null)
    {
        $sql = 'CREATE TABLE ' . $table . '(';
        if ($type == null) {
            $arrname = array_keys($name);
            $arrtype = array_values($name);
        } else {
            $arrname = explode("|", $name);
            $arrtype = explode("|", $type);
        }
        for ($i = 0; $i < count($arrname); $i++) {
            if ($i == count($arrname) - 1) {
                $sql = $sql . $arrname[$i] . "   " . $arrtype[$i] . "";
            } else {
                $sql = $sql . $arrname[$i] . "   " . $arrtype[$i] . ",";
            }
        }
        $sql = $sql . ');';
        $re = $this->query($sql);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SqlLite 删除数据库
     *
     * $table 表名
     */
    function drop($table)
    {
        $sql = 'DROP TABLE ' . $table . ';';
        $re = $this->query($sql);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SqlLite 插入数据
     */
    function insert($table, $name, $value = null)
    {
        $sql = "INSERT INTO " . $table . '(';
        if ($value == null) {
            $arrname = array_keys($name);
            $arrvalue = array_values($name);
        } else {
            $arrname = explode('|', $name);
            $arrvalue = explode('|', $value);
        }
        for ($i = 0; $i < count($arrname); $i++) {
            if ($i == count($arrname) - 1) {
                $sql = $sql . $arrname[$i];
            } else {
                $sql = $sql . $arrname[$i] . ",";
            }
        }
        $sql = $sql . ")VALUES(";
        for ($i = 0; $i < count($arrvalue); $i++) {
            if ($i == count($arrvalue) - 1) {
                $sql = $sql . "'" . $arrvalue[$i] . "'";
            } else {
                $sql = $sql . "'" . $arrvalue[$i] . "',";
            }
        }
        $sql .= ");";
        $re = $this->query($sql);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SqlLite 删除数据
     */
    function delete($table, $Conditionsname, $Conditionsvalue = null)
    {
        if ($Conditionsvalue != null) {
            $sql = "DELETE FROM " . $table . " WHERE " . $Conditionsname . "='" . $Conditionsvalue . "';";
        } else {
            $sql = "DELETE FROM " . $table . " WHERE ";
            $arrname = array_keys($Conditionsname);
            $arrvalue = array_values($Conditionsname);
            for ($i = 0; $i < count($arrname); $i++) {
                if ($i == count($arrname) - 1) {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "'";
                } else {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "',";
                }
            }
            $sql .= ';';
        }
        $re = $this->query($sql);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SqlLite 查询数据
     *
     * $table String 表名
     * $name String 查询字段
     * $Conditionsname 查询条件
     *
     * select("test","id,name,age,article",array("name"=>"张三"));
     */
    function select($table, $name, $Conditionsname, $Conditionsvalue = null)
    {
        if ($Conditionsvalue != null) {
            $sql = "SELECT " . $name . " FROM " . $table . " WHERE " . $Conditionsname . "='" . $Conditionsvalue . "';";
        } else {
            $sql = "SELECT " . $name . " FROM " . $table . " WHERE ";
            $arrname = array_keys($Conditionsname);
            $arrvalue = array_values($Conditionsname);
            for ($i = 0; $i < count($arrname); $i++) {
                if ($i == count($arrname) - 1) {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "'";
                } else {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "' and ";
                }
            }
            $sql .= ';';
        }
        $ret = $this->query($sql);
        $return = array();
        while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
            array_push($return, $row);
        }
        return $return;
    }

    /**
     * SqlLite 更新数据
     *
     * $table String 表名
     * $name String 修改的字段
     * $value String 更新的值
     * $Conditionsname 修改条件Array
     */
    function update($table, $name, $value, $Conditionsname, $Conditionsvalue = null)
    {
        if ($Conditionsvalue != null) {
            $sql = "UPDATE " . $table . " SET " . $name . "= '" . $value . "' WHERE " . $Conditionsname . "='" . $Conditionsvalue . "';";
        } else {
            $sql = "UPDATE " . $table . " SET " . $name . "= '" . $value . "' WHERE ";
            $arrname = array_keys($Conditionsname);
            $arrvalue = array_values($Conditionsname);
            for ($i = 0; $i < count($arrname); $i++) {
                if ($i == count($arrname) - 1) {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "'";
                } else {
                    $sql .= $arrname[$i] . '=' . "'" . $arrvalue[$i] . "' and ";
                }
            }
            $sql .= ';';
        }
        $re = $this->query($sql);
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * SqlLite 数据查询分组
     */
    function group($table, $name)
    {
        $sql = "SELECT " . $name . " FROM " . $table . ";";
        $return = array();
        $ret = $this->query($sql);
        while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
            array_push($return, $row[$name]);
        }
        return $return;
    }

    /**
     * SqlLite 数组对象转数组
     */
    function fecthall($sql)
    {
        $return = array();
        $ret = $this->query($sql);
        while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
            array_push($return, $row);
        }
        return $return;
    }
}
