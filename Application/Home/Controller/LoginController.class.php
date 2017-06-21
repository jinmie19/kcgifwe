<?php
namespace Home\Controller;

use Think\Controller;
use Home\Model\UserModel;
use Home\Model\LoginLogModel;
use Common\Org\smsapi;

class LoginController extends Controller
{
    //用户登录
    public function loginAction()
    {

        if (IS_POST){
            $phone=I('post.phone');
            $password=I('post.password');
            $User=new UserModel();
            $LoginLog=new LoginLogModel();

            $result=$User->findUser($phone);
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->ajaxResult(1,$v);
                    $this->success();
                }
            }
            //用户名匹配
            if(!$result){
                $this->ajaxResult(1,'用户不存在!');
            }

            //密码匹配
            if(md5($password.$result['salt']).$result['salt']!=$result['password']){
                $this->ajaxResult(1,'密码错误!');
            }

            //账号不能同时登陆验证

            if(session('loginKey')){
                if($result['ip']!=get_client_ip(0,true)){
                    $this->ajaxResult(1,'账号已被登录!');
                }
            }

            if(session('loginIp')){
                if($result['ip']!=get_client_ip(0,true)){
                    $data['error']=1;
                    $data['message']='账号已被登录!';
                   $this->ajaxReturn($data);
                }



            }


            $phone=$result['phone'];
            $data['loginTime'] = time();
            $data['ip']=get_client_ip(0,true);
            //更新用户登录时间
            if( $User->where("phone=$phone")->save($data)){
                session('phone',$result['phone']);
                session('loginKey',1);
                session('lastLoginTime',$result['logintime']);
                session('loginIp',get_client_ip());
                //用户登录日志
                if($LoginLog->insert($result['uid'],$result['phone'])){
                    $this->ajaxResult(0,'登录成功!');
                    $data['error']=0;
                    $data['message']='登录成功!';
                    $this->ajaxReturn($data);
                }else{
                    $this->ajaxResult(1,'登录失败!');
                }

            }

        }else{
            $this->display();
        }
    }

//注销登录
    public function loginOutAction(){
        session(null);
        $this->redirect('login','',1,'注销成功!');
    }

    //用户注册
    public function registerAction()
    {
        if (IS_POST){
            $User=new UserModel();
            //自动验证
            if (!$User->create(I('post.'))){
                foreach($User->getError() as $v){
                    $this->ajaxResult(1,$v);
                }
            }
            //验证码
            if(!$User->check_verify(I('post.verify'))){
                $this->ajaxResult(1,'验证码错误!');
            }
            //手机验证码
            if(I("post.message")!=session('message')){
                $this->ajaxResult(1,'手机验证码错误!');
            }
            //手机号唯一
            if($User->findUser(I('post.phone'))){
                $this->ajaxResult(1,'手机号已被注册!');
            }

            if($User->insert(I('post.'))){
                $this->ajaxResult(0,'注册成功!');
            }else{
                $this->ajaxResult(1,'注册失败!');
            }
        }else{
            $this->display();
        }
    }

    //找回密码
    public function seekPassAction(){
        if(IS_POST){
            $User=new UserModel();
            if(I('post.status')==1){
                /*//自动验证
                if (!$User->create(I('post.'))){
                    foreach($User->getError() as $v){
                        $this->ajaxResult(1,$v);
                    }
                }
                //验证码
                if(!$User->check_verify(I('post.verify'))){
                    $this->ajaxResult(1,'验证码错误!');
                }
                //手机验证码
                if(I("post.message")!=session('message')){
                    $this->ajaxResult(1,'手机验证码错误!');
                }*/
                $phone=I('post.phone');
                $result=$User->findUser($phone);
                if($result){
                    if($result['isrz']){
                        if($result['idtype']!=I('post.idType') || $result['idnumber']!=I('post.idNumber')){
                            $this->ajaxResult(1,'用户已实名，请正确填写实名信息!');
                        }else{
                            session('phone',$phone);
                            $this->ajaxResult(0,'继续以完成修改');
                        }
                    }else{
                        session('phone',$phone);
                        $this->ajaxResult(0,'继续以完成修改');
                    }
                }else{
                    $this->ajaxResult(1,'找不到用户!');
                }
            }

            if(I('post.status')==2){
                $result=$User->findUser(session('phone'));
                $User->password=md5(I('post.password').$result['salt']).$result['salt'];
                if($User->where('phone=%s',['%s'=>session('phone')])->save()!==false){
                    $this->ajaxResult(0,'继续以完成修改');
                }else{
                    $this->ajaxResult(1,'修改密码失败!');
                }
            }

        }else{
            @$status=I('get.status');
            if($status==''){
                $this->assign('status',1);
                $this->display();
            }
            if($status==2){
                $this->assign('status',2);
                $this->display();
            }
            if($status==3){
                $this->assign('status',3);
                $this->display();
            }
        }
    }
    public function verifyAction(){
        $config = array(
            'fontSize'  =>  '15',
            'length'    =>  '4',
            'codeSet'   =>  '0123456789',
            'useNoise'  =>  false,
            'imageH'    =>'28',
            'imageW'    =>'100',
        );
        $Verify = new \Think\Verify($config);
        $Verify -> entry();
    }

    //手机短信验证sms.cn
    public function sendMessageAction(){
        if($mobile=I('post.phone'))
        {
            $uid = 'kc2050';
            $pwd = 'kc2050';
            $api = new SmsApi($uid,$pwd);
            $number=$api->randNumber();
            session('message',$number);
            $contentParam = array(
                'code'		=> $number,
                'username'	=> $mobile,
            );
            $template = '403141';
            $result = $api->send($mobile,$contentParam,$template);
            if($result['stat']=='100')
            {
                echo '1';
            }
            else
            {
                echo '0';
            }
        }else{
            echo '0';
        }

    }

    protected  function ajaxResult($error=1,$message='错误'){
        $data['error']=$error;
        $data['message']=$message;
        $this->ajaxReturn($data);
    }
}