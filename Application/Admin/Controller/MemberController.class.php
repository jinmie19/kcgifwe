<?php
namespace Admin\Controller;
use Think\Controller;

class MemberController extends Controller
{
    public function cMemberAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }

    public function sMemberAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }
}