<?php
namespace Admin\Controller;
use Think\Controller;

class ReportController extends Controller
{
    public function dayAction()
    {
        if(IS_POST){

        }else{
//            echo date("Y-m-d",time())."<br />";
//            echo date("Y-m-d", strtotime("-1 days",strtotime(date("Y-m-d",time()))));;
//            exit;
//             $endDate=strtotime(date("Y-m-d",time()));
             $endDate=strtotime("2017-06-24");
             $starDate=strtotime("2017-06-22");
             for($endDate;$endDate>=$starDate;$endDate-=24*3600){

                    // echo date("Y-m-d",$endDate).'--'.$endDate.'<br>昨天结束的时间戳时间戳'.$endDate.'<br>';
                    // echo  $endTime = strtotime("-1 day",$endDate).'<br>';
                    $startTime = strtotime("-1 day",$endDate);
                    $map['time'] = array('between',array($startTime,$endDate));
                    $map['type'] = '卖出';
                    $result = M('Trade') -> where($map) ->select();
                    foreach ($result as $v){
                        $sum[date("Y-m-d",$startTime)]['number'] += $v['number'];
                        $sum[date("Y-m-d",$startTime)]['amount'] += $v['number']*$v['tradeprice'];
                        $sum[date("Y-m-d",$startTime)]['fee'] += $v['fee'];
                    }



             }
//             echo '<pre>';
//             print_r($sum);
            $this->assign('sum',$sum);
            $this->display();
        }
    }

    public function weekAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }

    public function monthAction()
    {
        if(IS_POST){

        }else{
            $this->display();
        }
    }
}