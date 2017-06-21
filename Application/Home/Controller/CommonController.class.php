<?php
namespace Home\Controller;

use Think\Controller;


class CommonController extends Controller
{
    public function _initialize(){
        if(session('loginKey')!=1){
            $this->redirect('/login','',1,'请先登录!');
        }
    }
}