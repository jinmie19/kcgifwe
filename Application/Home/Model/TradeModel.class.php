<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/5
 * Time: 17:21
 */
namespace Home\Model;
use Think\Model;

class TradeModel extends Model
{
    public function addTrade($data)
    {
        return  $this -> data($data) -> add();
    }

    public function signRand($len = 5){
        $chars = str_repeat('9638527410', 10);
        $chars = str_shuffle($chars);
        $str   = substr($chars, 0, $len);
        return $str;
    }
}