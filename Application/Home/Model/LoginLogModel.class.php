<?php
namespace Home\Model;
use Think\Model;

class LoginLogModel extends Model{
    protected $trueTableName = 'kc_login_log';

    public function insert($uid=0,$phone=''){
        $this->uid=$uid;
        $this->phone=$phone;
        $this->time=time();
        $this->ip=get_client_ip(0,true);
        $this->status=1;
        $result=$this->add();
        if($result){
            return true;
        }
        return false;
    }

}


