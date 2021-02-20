<?PHP
require "db.php";
$dbtest1 = new db("192.168.29.105","slave","root","mytest");
$dbtest2 = new db("192.168.29.105","slave","root","mytest2");
$dbtest3 = new db("192.168.29.105","slave","root","mytest3");

//为XA事务指定一个id，xid 必须是一个唯一值。
$xid1 = uniqid("");
$xid2 = uniqid("");
$xid3 = uniqid("");

//两个库指定同一个事务id，表明这两个库的操作处于同一事务中
$dbtest1->exec("XA START '$xid1'");//准备事务1
$dbtest2->exec("XA START '$xid2'");//准备事务2
$dbtest3->exec("XA START '$xid3'");//准备事务3

try {
    //$dbtest1

    $return = $dbtest1->exec("UPDATE t SET name='33333' WHERE id=1") ;
    echo "xa1:"; print_r($return);

    if(!in_array($return,['0','1'])) {

        throw new Exception("库1执行sql操作失败！");

    }

    //$dbtest2

    $return = $dbtest2->exec("UPDATE orders SET amount=99.00 WHERE id=1");
    echo "xa2:"; print_r($return);
    if(!in_array($return,['0','1'])) {

        throw new Exception("库2执行sql操作失败！");

    }

    //$dbtest3
    $return = $dbtest3->exec("UPDATE order_index SET order_id=1 WHERE id=1");
    echo "xa3:"; print_r($return);

    if(!in_array($return,['0','1'])) {

        throw new Exception("库3执行sql操作失败！");

    }

    //阶段1：$dbtest1提交准备就绪

    $dbtest1->exec("XA END '$xid1'");

    $dbtest1->exec("XA PREPARE '$xid1'");

    //阶段1：$dbtest2提交准备就绪

    $dbtest2->exec("XA END '$xid2'");

    $dbtest2->exec("XA PREPARE '$xid2'");

    //阶段1：$dbtest3提交准备就绪

    $dbtest3->exec("XA END '$xid3'");

    $dbtest3->exec("XA PREPARE '$xid3'");


    //阶段2：提交两个库

    $dbtest1->exec("XA COMMIT '$xid1'");

    $dbtest2->exec("XA COMMIT '$xid2'");

    $dbtest3->exec("XA COMMIT '$xid3'");

}

catch (Exception $e) {

    //阶段2：回滚

    $dbtest1->exec("XA ROLLBACK '$xid1'");

    $dbtest2->exec("XA ROLLBACK '$xid2'");

    $dbtest3->exec("XA ROLLBACK '$xid3'");

    die("Exception:".$e->getMessage());

}

echo "执行完毕";exit;
/*
$dbtest1->close();
$dbtest2->close();*/

?>
