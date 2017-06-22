<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21
 * Time: 8:52
 */

namespace Admin\Controller;
use Think\Controller;

class UserController extends Controller
{
    public function userAction()
    {
        $User=M('user');
        if(IS_POST){

        }else{
            $list = M('User')->where('level=1')-> select();
            $this->assign('results',$list);
            $this->display();
        }
    }

    public function auditAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }

    public function loginRecordAction()
    {
        if(IS_POST){

        }else{
            $list=M('login_log')->order('time desc')->select();
            $this->assign('results',$list);
            $this->display();
        }
    }
}