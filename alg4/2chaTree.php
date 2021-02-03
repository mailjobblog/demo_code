<?php
// 二分查找法
function binSearch($arr, $search)
{
    $height = count($arr) - 1;
    $low = 0;
    while ($low <= $height) {
        $mid = floor(($low + $height) / 2); //获取中间数
        if ($arr[$mid] == $search) {
            return $mid; //返回
        } elseif ($arr[$mid] < $search) { //当中间值小于所查值时，则$mid左边的值都小于$search，此时要将$mid赋值给
            $low = $mid + 1;
        } elseif ($arr[$mid] > $search) { //中间值大于所查值,则$mid右边的所有值都大于$search,此时要将$mid赋值给$height
            $height = $mid - 1;
        }
    }
    return "查找失败";
}


$arr = array(5, 10, 19, 22, 33, 44, 48, 55, 60, 68);

var_dump(binSearch($arr, 55));
