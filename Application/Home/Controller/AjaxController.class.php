<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/12
 * Time: 9:15
 */
namespace Home\Controller;
use Think\Controller;

class AjaxController extends Controller
{

    public function ajaxAction()
    {

        $cond['status'] = array('EQ','委托中');
        $result = M('Delegate')->where($cond)->select();

        if(! $result) return;

        foreach ($result as $k=>$v){
            if($v['type'] == '买入'){
                A('Trade')->trade($v['phone'],$v['balance'],$v['price'],'买入','ajax');
            }else{
                A('Trade')->trade($v['phone'],$v['balance'],$v['price'],'卖出','ajax');
            }
        }

    }
}