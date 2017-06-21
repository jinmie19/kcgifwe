<?php
namespace Home\Model;
use Think\Model;

class SafetyRecordModel extends Model{
    protected $trueTableName = 'kc_safety_record';

    public function insert($uid=0,$phone='',$type=7){
        $this->uid=$uid;
        $this->phone=$phone;
        $this->time=time();
        $this->ip=get_client_ip();
        $this->status=1;
        $this->type=$type;
        $result=$this->add();
        if($result){
            return true;
        }
        return false;
    }

}


