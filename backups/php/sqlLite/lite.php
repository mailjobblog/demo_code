<?php
header("content-type:text/html;charset=utf-8");
require_once("sqlLite.class.php");

$sqlLite = new sqlLite("data.db");

//创建数据库
//$result = $sqlLite->create("test", array("id"=>"integer PRIMARY KEY autoincrement", "name"=>"VARCHAR(50)", "age"=>"INT  NOT NULL","article"=>"TEXT    NOT NULL"));


//数据入库
$result = $sqlLite->insert("test", array("name" => "张三", "age" => "20", "article" => "自 PHP 5.3.0 起默认启用 SQLite3 扩展。可以在编译时使用 --without-sqlite3 禁用 SQLite3 扩展。"));
var_dump($result);


//查询
//$result = $sqlLite->select("test","id,name,age,article",array("name"=>"张三"));
//print_r($result);


//更新数据
