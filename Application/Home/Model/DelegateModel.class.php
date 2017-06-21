<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/3
 * Time: 18:15
 */
namespace Home\Model;
use Think\Model;

class DelegateModel extends Model
{
    // 查找卖方中(卖出价 <= 买入价)卖价最低,时间最早的一条卖出记录
    public function findSell($phone,$price)
    {
        $map['phone'] = array('NEQ',$phone);
        $map['type'] = array('EQ','卖出');
        $map['status'] = array('EQ','委托中');
        //$map['balance'] = array('NEQ',0);
        $map['price'] = array('ELT',$price);

        return $this->where($map)->order('price asc,time asc')->find();
    }

    // 查找买方中(买入价 >= 卖出价)买价最高,时间最早的一条卖出记录
    public function findBuy($phone,$price)
    {
        $map['phone'] = array('NEQ',$phone);
        $map['type'] = array('EQ','买入');
        $map['status'] = array('EQ','委托中');
        //$map['balance'] = array('NEQ',0);
        $map['price'] = array('EGT',$price);

        return $this->where($map)->order('price desc,time asc')->find();
    }

    public function addOrder($data)
    {
        $data['sign'] = 'd'.$this->signRand().time();
        $data['deal'] = isset($data['deal']) ? $data['deal'] : 0.0;
        return  $this -> data($data) -> add();
    }

    public function updateOrder($phone,$data)
    {
        $data['status'] = isset($data['status']) ? $data['status'] : '委托中';
        $data['balance'] = isset($data['balance']) ? $data['balance'] : 0;
        return $this -> where('phone=%s',$phone)->save($data);
    }

    public function signRand($len = 5){
        $chars = str_repeat('9638527410', 10);
        $chars = str_shuffle($chars);
        $str   = substr($chars, 0, $len);
        return $str;
    }

}