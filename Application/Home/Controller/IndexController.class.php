<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends Controller {

    public function indexAction(){
        if(IS_POST){

        }else{
            $this->assign('name','tp.com');
            $this->display();
        }
    }

    public function coinAction(){
        $model = M('coin');
        $result = $model->select();
        echo '<pre>';
        var_dump($result);
        exit;
    }

    public function insertAction()
    {
        $data = array();
        $model = M('Coin');
        for($j=1,$i=0;$j<=100000;$j++,$i++){
            //|| () || ($j>50000&&$j<=55000) || () || () || ()
            if ($j>95000){
                $data[$i] = array('num'=>$j,'type'=>'自留','status'=>'库存');
            }
//            else{
//                $data[$j-1] = array('num'=>$j,'type'=>'认购','status'=>'库存');
//            }
        }

        $result = $model->addAll($data);
        if ($result){
            echo '批量插入ok3';
        }


        exit;

    }

    public function verifyAction(){

        $config = array(
            'fontSize'  =>  '15',
            'length'    =>  '4',
            'codeSet'   =>  '0123456789',
            'useNoise'  =>  false,
        );
        $Verify = new \Think\Verify($config);
//        $Verify = new \Think\Verify();

        $Verify -> entry();
    }

    public function doVerifyAction(){
        if (IS_POST){
            $code = $_POST['verify'];
            $verify = new \Think\Verify();
            if($verify->check($code)){
                echo "<script>alert('验证成功!')</script>";
            }else{
                echo "<script>alert('验证成功!')</script>";
            }
        }else{
            echo '禁止访问!';
            exit;
        }
    }
}