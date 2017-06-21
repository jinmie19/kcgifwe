<?php
namespace Home\Controller;

use Think\Controller;

class CoinController extends Controller
{
    public function indexAction(){
        $m = M('Coin');
        $this->assign('coins',$m->select());
        $this->display();
    }
}