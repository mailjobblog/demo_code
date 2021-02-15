<?php
try {
  $pdo=new pdo("mysql:host=localhost;dbname=mysql_php", "root", "root", array(PDO::ATTR_AUTOCOMMIT=>0));//最后是关闭自动提交
  $pdo->setAttribute(PDO::ATTR_ERRMODE,  PDO::ERRMODE_EXCEPTION);//开启异常处理
  try {
    $pdo->beginTransaction();//开启事务处理
    $product_id = 2;//要修改的商品id
    $sql = "select * from product where id = {$product_id}";
    $restful=$pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!$restful) {
      echo "商品不存在：".$e->getMessage();
      exit;
    }else{
      $version = $restful['version'];
      $sql = "update product set product_stock=product_stock-1,version = version+1 where id = {$product_id} and version = {$version}";
      $restful=$pdo->exec($sql);
      if ($restful) {
        $pdo->commit();//事务提交
        echo "修改库存成功";
        exit;
      }else{
        echo "修改库存失败";
        exit;
      }
    }

  } catch (\Exception $e) {
    echo $sql.$e->getMessage();
    exit;
  }

} catch (\Exception $e) {
  echo "数据库连接失败：".$e->getMessage();
  exit;
}
