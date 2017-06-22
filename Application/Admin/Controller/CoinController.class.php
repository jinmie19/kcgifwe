<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21
 * Time: 8:56
 */

namespace Admin\Controller;
use Think\Controller;

class CoinController extends Controller
{
    public function coinAction()
    {
        if(IS_POST){

        }else{

            $list = M('Coin') ->order('sign asc')-> select();
            $this->assign('list',$list);
            $this->display();
            /**生成选手编号**/
            //0000
//            $VoteNum = 1;
//            if (isset($result)) {
//                // 取 750001的最后数字
//                $VoteNum = preg_replace('/^0*/', '', substr($result->getVoteNum(), -4));
//                $VoteNum = (int)$VoteNum + 1;
//            }
//            // 68 0001
//            // 1=>000 2=>00 3=>0 4=>''
//            $votenums = array(
//                1 => '000',
//                2 => '00',
//                3 => '0',
//                4 => ''
//            );
//            $VoteNum = $wx_vote_number . $votenums[strlen($VoteNum)].$VoteNum;

//            for($i=1;$i<=10;$i++){
//                $data['type'] = '挖币';
//                $signs = array(
//                    1   =>  '0000',
//                    2   =>  '000',
//                    3   =>  '00',
//                    4   =>  '0',
//                    5   =>  '',
//                );
//                $data['sign'] = 'XYC'.$signs[strlen($i)].$i;
////                $data['sign'] = 'XYC'.$i;
//                M('Coin')->add($data);
//            }
//            echo "is ok";
        }
    }
}