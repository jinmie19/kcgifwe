<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21
 * Time: 8:56
 */

namespace Admin\Controller;
use Think\Controller;

class CoinController extends Controller
{
    public function coinAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }
}