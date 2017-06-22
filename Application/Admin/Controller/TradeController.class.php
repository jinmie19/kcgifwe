<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21
 * Time: 8:44
 */

namespace Admin\Controller;
use Think\Controller;

class TradeController extends Controller
{
    public function currentDelegateAction()
    {
        if(IS_POST){

        }else{
            // 当前委托(委托中)记录
            $delegate = M('Delegate') -> where('status=%s','1') -> select();
            $this->assign('delegate',$delegate);
            $this->display();
        }
    }

    public function historyDelegateAction()
    {
        if(IS_POST){

        }else{
            // 历史委托(取消委托,已成交)记录
            $map['status'] = array('NEQ','委托中');
            $oldResult = M('delegate')->where($map)-> select();
            //echo '<pre>';
            $oldResult = array_map(function($v){
                $v['trading'] = $v['number'] - $v['balance']; //成交量
                if($v['deal'] == 0){ // 此委托记录非一次成交
                    $map['delegate_id'] = array('EQ',$v['sign']);
                    $trade = M('Trade')->where($map)->select();
                    $sum = 0.0;
                    $num = 0;
                    foreach ($trade as $value){
                        $sum += $value['tradeprice'] * $value['number'];
                        $num += $value['number'];
                    }
                    $v['turnover'] = $sum; //成交额
                    $v['average'] = $v['turnover'] / $num ;
                }else{
                    $v['turnover'] = $v['trading'] * $v['deal']; //成交额
                    $v['average'] = $v['turnover'] / $v['trading']; //平均成交价
                }
                return $v;
            },$oldResult);
            //print_r($oldResult);exit;
            $this->assign('oldResult',$oldResult);
            $this->display();
        }
    }

    public function tradeRecordAction()
    {
        if(IS_POST){

        }else{
            // 当前委托(委托中)记录
            $trade = M('Trade')-> select();
            $trade = array_map(function($v){
                $v['sum'] = $v['tradeprice'] * $v['number'];
                return $v;
            },$trade);
            $this->assign('trade',$trade);
            $this->display();
        }
    }
}