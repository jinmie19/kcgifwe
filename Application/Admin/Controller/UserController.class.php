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
        $results=$User->select();
        if(IS_POST){

        }else{
            $data['results']=$results;
            $this->assign($data);
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
            $this->display();
        }
    }
}