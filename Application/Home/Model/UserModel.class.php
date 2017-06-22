<?php
namespace Home\Model;
use Think\Model;

class UserModel extends Model{

    const MODEL_REGISTER = 101; // 自定义的时机常量

    // 开启批量验证
    public $patchValidate = true;

    // validate 属性存储了自定义的验证规则, 多条规则, 使用数组存储
    protected $_validate = [
        //每个元素表示一条规则, 使用数组表示
        ['phone','require','请输入手机号',self::EXISTS_VALIDATE],
        ['phone','number','手机号格式不对',self::EXISTS_VALIDATE],
        ['phone','','手机号不能重复',self::EXISTS_VALIDATE,'unique',self::MODEL_REGISTER],
        ['password','require','请输入密码',self::EXISTS_VALIDATE],
        ['repassword','require','请确认密码',self::EXISTS_VALIDATE],
        ['repassword','password','两次密码不一致!',0,'confirm',self::EXISTS_VALIDATE],
        ['mediumer','require','请输入所属会员编号',self::EXISTS_VALIDATE],
        ['verify','require','请输入图形验证码',self::EXISTS_VALIDATE],
        ['message','require','请输入手机验证码',self::EXISTS_VALIDATE],
        ['oldPwd','require','请输入原始密码',self::EXISTS_VALIDATE],
        ['oldMoneyPwd','require','请输入原始资金密码',self::EXISTS_VALIDATE],
        ['moneyPwd','require','请输入资金密码',self::EXISTS_VALIDATE],
        ['remoneyPwd','require','请输入资金验证码',self::EXISTS_VALIDATE],
        ['email','require','请输入邮箱地址',self::EXISTS_VALIDATE],
        ['email','email','请输入有效邮箱',self::EXISTS_VALIDATE],
        ['place','require','请输入地址',self::EXISTS_VALIDATE],
        ['realName','require','请输入姓名',self::EXISTS_VALIDATE],
    ];
   //插入用户信息
    public function insert($data=''){
        $this->uid=$data['mediumer'].'00002';
        $this->phone=$data['phone'];
        $this->salt=$this->randNumber();
        $this->password=md5($data['password'].$this->salt).$this->salt;
        $this->mediumer=$data['mediumer'];
        $this->registerTime=time();
        $this->isBindPhone=1;
        $this->level=1;
        $this->region=$data['region'];
        $result=$this->add();
        if($result!=false){
            return true;
        }
        return false;
    }
    //产生随机数
    public function randNumber($len = 8)
    {
        $chars = str_repeat('0123456789qwertyuiopasdfghjklzxcvbnm', 10);
        $chars = str_shuffle($chars);
        $str   = substr($chars, 0, $len);
        return $str;
    }
    //根据用户phone查找用户信息
    public function findUser($phone)
    {
        return $this-> where('phone=%s',['%s'=>$phone])->find();
    }

    // 更新用户轩辕币数量
    public function saveNum($phone,$data)
    {
        if($this -> where('phone=%s',['%s'=>$phone])->save($data)){
            return [ 'error' => 0,'time'  => time() ];
        }else{
            return [ 'error' => 1 ];
        }
//        return $this -> where('phone=%s',$phone)->save($data1);
//        return $this->save($data1);
    }
    public function check_verify($code,$id=''){
        $verify = new \Think\Verify();
        return $verify->check($code,$id);
    }



}


