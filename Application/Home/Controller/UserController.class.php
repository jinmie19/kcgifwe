<?php
namespace Home\Controller;

use Home\Model\SafetyRecordModel;
use Think\Controller;
use Home\Model\UserModel;
use Think\Db;



date_default_timezone_set('PRC');

class UserController extends CommonController
{
    //基本信息
    public function basicInformationAction(){

        $User=new UserModel();
        $result=$User->where('phone=%s',['%s'=>session('phone')])->find();
        $arr=array(
           'isRz'=>(int)$result['isrz'],
           'isBindEmail'=>$result['isbindemail'],
            'isMoneyPwd'=>$result['ismoneypwd'],
           'realName'=>$result['realname'],
           'email'=>$result['email'],
           'phone'=>$result['phone'],
           'uid'=>$result['uid'],
           'place'=>$result['place'],
           'moneyPwd'=>$result['moneyPwd'],
           'lastLoginTime'=>date("Y-m-d H:i:s",session('lastLoginTime'))
        );
        $this->assign($arr);
        $this->display();
    }

    //实名认证
    public function realNameAction(){
        $User=new UserModel();
        $SafetyRecord=new SafetyRecordModel();
        $result=$User->where('phone=%s',['%s'=>session('phone')])->find();
        if(IS_POST){
            //自动验证
          /* if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->redirect('realName','',1,$v);
                }
            }
          //验证码
            if($_POST['message']!=session('message')){
                $this->redirect('realName','',1,'手机验证码输入错误!');
            }
            */
            $User->realName=I('post.realName');
            $User->isRz=1;
            $User->idType=I('post.idType');
            $User->idNumber=I('post.idNumber');
            if($User->where('phone=%s',['%s'=>session('phone')])->save()){
                //安全日志
                $SafetyRecord->insert($result['uid'],session('phone'),6);
                $this->redirect('basicInformation','',1,'实名认证成功!');
            }else{
                $this->redirect('realName','',1,'实名认证失败!');
            }
        }else{
            $arr=array(
                'isRz'=>(int)$result['isrz'],
                'phone'=>$result['phone'],
                'uid'=>$result['uid'],
            );
            $this->assign($arr);
            $this->display();
        }
    }

    //绑定邮箱
    public function bindEmailAction(){
        $User=new UserModel();
        $SafetyRecord=new SafetyRecordModel();
        $result=$User->where('phone=%s',['%s'=>session('phone')])->find();
        if(IS_POST){
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->redirect('bindEmail','',1,$v);
                }
            }
            //验证码
            if(!$this->check_verify(I('post.verify'))){
                $this->redirect('bindEmail','',1,'验证码输入错误!');
            }
            //邮箱验证码
            if($_POST['message']!=session('message')){
                $this->redirect('bindEmail','',1,'邮箱验证码输入错误!');
            }
            //邮箱唯一验证
            foreach($User->getField('email',true) as $v){
                if(I('post.email')==$v){
                    $this->redirect('bindEmail','',1,'邮箱已被绑定!');
                }
            }

            $User->email=I('post.email');
            $User->isBindEmail=1;
            $result=$User->where('phone=%s',['%s'=>session('phone')])->save();
            if($result){
                //安全日志
                $SafetyRecord->insert($result['uid'],I('post.phone'),4);
                $this->redirect('basicInformation','',1,'绑定邮箱成功！');
            }else{
                $this->redirect('bindEmail','',1,'绑定邮箱失败！');
            }
        }else{
            $arr=[
              'uid'=>$result['uid'],
            ];
            $this->assign($arr);
            $this->display();
        }


}

    //修改手机号
    public function modifyPhoneAction(){
        $User=new UserModel();
        $SafetyRecord=new SafetyRecordModel();
        $result=$User->findUser(session('phone'));
        if (IS_POST){
            //自动验证
            /*if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->ajaxResult(1,$v);
                }
            }*/
            //验证码

            if(!$User->check_verify(I('post.verify'))){
                $this->ajaxResult(1,'验证码错误！');
            }
             //手机验证码
            if(I('post.message')!=session('message')){
                $this->ajaxResult(1,'手机验证码错误!');
            }
            //手机号唯一
            if($User->findUser(I('post.phone'))){
                $this->ajaxResult(1,'手机号码已被注册!');
            }
            $uid=$result['uid'];
            $User->phone=I('post.phone');
            if($User->where("uid=$uid")->save()){
                //安全日志
                $SafetyRecord->insert($result['uid'],I('post.phone'),3);
                //修改存储的session值
                session('phone',I('post.phone'));
                $this->ajaxResult(0,'修改手机号码成功!');
            }else{
                $this->ajaxResult(1,'修改手机号码失败!');
            }
        }else{
            $arr=array(
                'oldPhone'=>$result['phone'],
            );
            $this->assign($arr);
            $this->display();
        }
    }

    //修改登录密码
    public function modifyPwdAction(){
        $SafetyRecord=new SafetyRecordModel();
        $User=new UserModel();
        $result=$User->findUser(session('phone'));
        if(IS_POST){
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->ajaxResult(1,$v);
                }
            }
            //原始密码验证
            if(md5(I('post.oldPwd').$result['salt'])!=substr($result['password'],0,32)){
                $this->ajaxResult(1,'旧密码错误!');
            }
            $num=$result['salt'];
            $User->password=md5(I('post.password').$num).$num;
            if($User->where('phone=%s',['%s'=>session('phone')])->save()){
                //安全日志
                $SafetyRecord->insert($result['uid'],session('phone'),1);
                $this->ajaxResult(0,'修改登录密码成功!');
            }
        }else{
            $arr['phone']=$result['phone'];
            $this->assign($arr);
            $this->display();
        }
    }

    //资金密码相关操作
    public function modifyMoneyPwdAction(){
        $SafetyRecord=new SafetyRecordModel();
        $User=new UserModel();
        $result=$User->findUser(session('phone'));
        if(IS_POST){
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->ajaxResult(1,$v);
                }
            }
            //手机验证码
            if(I('post.message')!=session('message')){
                $this->ajaxResult(1,'手机验证码错误!');
            }
            /*//原始资金密码
            if($result['ismoneypwd']==1){
                if(md5(I('post.oldMoneyPwd').$result['salt'])!=substr($result['moneypwd'],0,32)){
                    $this->redirect('modifyMoneyPwd','',1,'原始资金密码错误!');
                }
            }*/
            $User->isMoneyPwd=1;
            $User->moneyPwd=md5(I('post.moneyPwd').$result['salt']).$result['salt'];
            if($User->where('phone=%s',['%s'=>session('phone')])->save()){
                //安全日志
                $SafetyRecord->insert($result['uid'],session('phone'),2);
                $this->ajaxResult(0,'修改资金密码成功!');
            }else{
                $this->ajaxResult(1,'修改资金密码失败!');
            }
        }else{
            $arr=array(
                'isMoneyPwd'=>$result['ismoneypwd'],
                'phone'=>$result['phone'],
            );
            $this->assign($arr);
            $this->display();
        }
    }
    //收货地址
    public function placeAction()
    {
        $User=new UserModel();
        $SafetyRecord=new SafetyRecordModel();
        $result=$User->where('phone=%s',['%s'=>session('phone')])->find();
        if(IS_POST){
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->redirect('place','',1,$v);
                }
            }
            //手机验证
            /*if(I('post.message')!=session('message')){
                $this->redirect('place','',1,'手机验证码输入错误!');
            }*/
            $User->place=I('post.place');
            if($User->where('phone=%s',['%s'=>session('phone')])->save()){
                //安全日志
                $SafetyRecord->insert($result['uid'],session('phone'),5);
                $this->redirect('basicInformation','',1,'设置收货地址成功!');
            }
        }else{
            $arr=array(
                'uid'=>$result['uid'],
                'place'=>$result['place'],
                'phone'=>$result['phone'],
            );
            $this->assign($arr);
            $this->display();
        }
    }

    //邮箱发送验证
    public function sendEmailAction(){
        if(IS_POST){
            vendor('PHPMailer.class#smtp');
            vendor('PHPMailer.class#phpmailer');
            $User=new UserModel();
            $num=$User->randNumber(6);
            session('message',$num);
            $mail= new \PHPMailer();
            $body= "欢迎使用轩辕币平台，您的验证码是  <span style='color: red'>$num</span> ,此验证码5分钟内有效";
            $mail->IsSMTP();
            $mail->Host       = "smtp.163.com";
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = true;
            $mail->Username   = "18256066673@163.com";
            $mail->Password   = "wr1003";
            $mail->CharSet  = "utf-8";
            $mail->Subject    = "邮箱验证";
            $mail->SetFrom('18256066673@163.com', 'test');
            $mail->MsgHTML($body);
            $mail->AddAddress(I('post.email'), "王瑞");//发送邮件
            if(!$mail->Send()) {
                return 0;
            } else {
                return 1;
            }
        }

    }


    //上传组件
    public function uploadAction(){
        if(IS_POST){
            $upload = new \Think\Upload();
            $upload->maxSize = 10240000 ;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Uploads/';
            $upload->savePath = '';
            $upload->autoSub = true;
            $upload->subName = array('date','Y-m-d');
            $info = $upload->upload();
            if($info){
                $data['error'] = 0;
                $data['url'] = $info[0]['savepath'].$info[0]['savename'];
                $this->ajaxReturn($data);
            }else{
                //return json_encode(array("error"=>"上传有误，清检查服务器配置！"));
            }

        }
    }

    protected  function ajaxResult($error=1,$message='错误'){
        $data['error']=$error;
        $data['message']=$message;
        $this->ajaxReturn($data);
    }
}

