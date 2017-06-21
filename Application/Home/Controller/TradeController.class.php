<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/24
 * Time: 16:51
 */

namespace Home\Controller;
use Think\Controller;


class TradeController extends Controller
{
    // 封装ajax请求返回信息
    protected function ajaxResult($code,$message)
    {
        $data['error'] = $code;
        $data['message'] = $message;
        $this->ajaxReturn($data);
    }
    // 随机生成委托单号
    protected function signRand($len = 4){
        $chars = str_repeat('9638527410', 10);
        $chars = str_shuffle($chars);
        $str   = substr($chars, 0, $len);
        return $str;
    }
    // 事务处理结果,关闭事务
    protected function transResult($type,$message=''){
        if($type == 'commit'){
            M()->commit();
            $this->ajaxResult(0,$message.'成功!');
        }else{
            M()->rollback();
            $this->ajaxResult(1,$message.'失败!');
        }
    }

    public function indexAction()
    {
        if(IS_POST){

        }
        else{
            echo md5('18256089792jrenet2e');exit();
            session('phone','18256089792');

            // 取用户信息
            $phone = session('phone');
            $user = M('User') -> where('phone=%s',$phone)->find();

            // 当前委托(委托中)记录
            $result = M('delegate')->where('phone=%s and status=%s',[$phone,'1'])->order('time desc')->select();

            // 当前-卖出-成交记录
            //$sellList = M('delegate')->where('phone=%s and type=%s',[$phone,'2'])-> select();
            //echo '<pre>';print_r($sellList);exit;
            // 当前-买入-成交记录
            //$buyList = M('delegate')->where('phone=%s and type=%s',[$phone,'1'])-> select();

            // 历史委托(取消委托,已成交)记录
            $map['status'] = array('NEQ','委托中');
            $map['phone'] = array('EQ',$phone);
            $oldResult = M('delegate')->where($map)-> select();
            $oldResult = array_map(function($v){
                $v['trading'] = $v['number'] - $v['balance']; //成交量
                $v['turnover'] = $v['trading'] * $v['price']; //成交额
                $v['average'] = $v['turnover'] / $v['trading']; //平均成交价
                return $v;
            },$oldResult);

            // 交易记录
            $tradeResult = M('Trade')->where('phone=%s',$phone)->select();




            $this->assign('results',$result);
            //$this->assign('sellLists',$sellList);
            //$this->assign('buyLists',$buyList);
            $this->assign('oldResults',$oldResult);
            $this->assign('user',$user);
            $this->assign('tradeResults',$tradeResult);

            $this->display();
        }
    }

    /**
     * @param $phone
     * @param $number
     * @param $price
     * @param string $type
     */
    public function trade($phone,$number,$price,$type='买入',$method='trade')
    {
        if($type == '买入'){
            // 取用户的购买价格和购买数量
            $data['price'] = $price;    // 买入价格
            $data['number'] = $number;  // 买入量
            M()->startTrans();
            // 查找卖方中(卖出价 <= 买入价)卖价最低,时间最早的一条卖出记录,并且 剩余卖出量 不为 0
            $result = D('Delegate') -> findSell($phone,$data['price']);
            // 卖方委托中有一条 则可触发交易
            if($result){

                //获取买方/卖方信息,进行交易处理
                $userBuy = D('User') -> findUser($phone);
                $userSell = D('User') -> findUser($result['phone']);

                // 买入量 = 卖方 剩余卖出量
                if($data['number'] == $result['balance']){

                    //买家买入对应数量
                    $userBuy['number'] =  $userBuy['numcoin'] + $data['number'];
                    $userBuy['account'] =  $userBuy['account'] - $data['number']*$result['price'];
                    $data1 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);

                    //卖家卖出对应数量
                    $userSell['number'] = $userSell['numcoin'] - $data['number'];
                    $userSell['account'] = $userSell['account'] + $data['number']*$result['price']*0.997;
                    $fee = $data['number']*$result['price']*0.003;// 0.3%的手续费,先放这里
                    $data2 = [
                        'numCoin'=>$userSell['number'],
                        'account'=>$userSell['account']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 买方历史委托 买方交易记录 和 卖方交易记录
                        // 历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
                        $data3 = [ //买方 历史 委托 数据
                            'uid'=>$userBuy['uid'],
                            'phone'=>$userBuy['phone'],
                            'time'=>$resultBuy['time'],
                            'hour'=>$resultBuy['time'],
                            'price'=>$data['price'], //委托价格
                            'deal'=>$result['price'], //以卖方价格成交 = 成交价格
                            'number'=>$data['number'], //委托数量(正好成交)
                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
                            'status'=>'已成交'
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);

                        if(! $result3){
                            $this->transResult('rollback','买入');
                        }

                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //买方交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$data['price'],    // 委托价格
                            'tradePrice'=>$result['price'],     // 买入价格
                            'number'=>$data['number'],          // 买入数量(正好成交)
                            'sid'=>$userSell['phone'],          // 卖方uid
                            'sphone'=>$userSell['phone'],       // 卖方phone
                            'delegate_id'=>$result['sign']        // 卖家的委托id号
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);

                        if(! $result4){
                            $this->transResult('rollback','买入');
                        }

                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','2',$userBuy['phone']) -> order('hour desc') -> find();
                        $data5 = [ //卖方交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$result['price'], //委托价格
                            'tradePrice'=>$result['price'], //卖出价格
                            'number'=>$data['number'], //卖出数量(正好成交)
                            'sid'=>$userBuy['uid'], //buy方uid
                            'sphone'=>$userBuy['phone'], //buy方phone
                            'type'  => '卖出',
                            'delegate_id'=> $delegateBuy['sign'],// 记录买方的委托号
                            'fee'=>$fee
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);

                        if(!$result5){
                            $this->transResult('rollback','买入');
                        }

                        $data6 = [ //卖方 更新 委托记录
                            'hour'=>$resultSell['time'],
                            //'balance'=>0,
                            'status'=>'已成交'
                        ];
                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);

                        if(! $result6){
                            $this->transResult('rollback','买入');
                        }

                        $this->transResult('commit','买入');

                    }
                    else{
                        $this->transResult('rollback','买入');
                    }
                }
                // 买入量 < 卖出 (买方买入量 < 卖方剩余卖出量)
                elseif ($data['number'] < $result['balance']){

                    // 买入方 足量 买入
                    $userBuy['number'] =  $userBuy['numcoin'] + $data['number'];
                    $userBuy['account'] =  $userBuy['account'] - $data['number']*$result['price'];
                    $data1 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);

                    //卖出方 卖出 买入的数量 剩余卖出量继续委托
                    $userSell['number'] = $userSell['numcoin'] - $data['number'];
                    $userSell['account'] = $userSell['account'] - $data['number']*$result['price']*0.997;
                    $fee = $data['number']*$result['price']*0.003;
                    $data2 = [
                        'numCoin'=>$userSell['number'],
                        'account'=>$userSell['account']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 买方历史委托 买方交易记录 和 买方交易记录 卖方继续委托
                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
                        $data3 = [ //买方历史委托数据
                            'uid'=>$userBuy['uid'],
                            'phone'=>$userBuy['phone'],
                            'time'=>$resultBuy['time'],
                            'hour'=>$resultBuy['time'],
                            'price'=>$data['price'], //委托价格
                            'deal'=>$result['price'], //以卖方价格成交 = 成交价格
                            'number'=>$data['number'], //委托数量(正好成交)
                            //'balance'=>$data['number'], //剩余委托数量(正好成交,默认0)
                            'status'=>'已成交'
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);
                        if(! $result3){
                            $this->transResult('rollback','买入');
                        }

                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //买方交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$data['price'],    // 委托价格
                            'tradePrice'=>$result['price'],     // 买入价格
                            'number'=>$data['number'],          // 买入数量(正好成交)
                            'sid'=>$userSell['phone'],          // 卖方uid
                            'sphone'=>$userSell['phone'],       // 卖方phone
                            'delegate_id'=>$result['sign']      // 卖家的委托编号
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);
                        if(! $result4){
                            $this->transResult('rollback','买入');
                        }

                        //找出刚刚的买家买入历史委托记录
                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','2',$userBuy['phone']) -> order('hour desc') -> find();
                        $data5 = [ //卖方交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$result['price'], //委托价格
                            'tradePrice'=>$result['price'], //卖出价格
                            'number'=>$data['number'], //卖出数量(买家买入数量)
                            'sid'=>$userBuy['uid'], //buy方uid
                            'sphone'=>$userBuy['phone'], //buy方phone
                            'type'  => '卖出',
                            'fee'  => $fee,
                            'delegate_id'=> $delegateBuy['sign']// 记录买家刚刚买入历史委托记录的委托号
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);
                        if(!$result5){
                            $this->transResult('rollback','买入');
                        }

                        $resultNum = $result['balance'] - $data['number']; // 卖方 剩余 委托量
                        $data6 = [ //卖方更新委托记录 剩余继续委托--委托中
                            'hour'=>$resultSell['time'],
                            'balance'=>$resultNum,
                            //'status'=>'委托中',
                        ] ;
                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
                        if(! $result6){
                            $this->transResult('rollback','买入');
                        }

                        $this->transResult('commit','买入');

                    }
                    else{
                        $this->transResult('rollback','买入');
                    }
                }
                // 买入量 > 卖出量 (买方买入量 > 卖方剩余卖出量)
                elseif ($data['number'] > $result['balance']){

                    // 买入 卖出的数量 剩余买入量进入委托中
                    $userBuy['number'] =  $userBuy['numcoin'] + $result['balance'];
                    $userBuy['account'] = $userBuy['account'] - $result['balance']*$result['price'];
                    $data1 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);

                    // 卖出方足量卖出剩余量
                    $userSell['number'] = $userSell['numcoin'] - $result['balance'];
                    $userSell['account'] = $userSell['account'] + $result['balance']*$result['price']*0.997;
                    $fee = $result['balance']*$result['price']*0.003;
                    $data2 = [
                        'numCoin'=>$userSell['number']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 买方交易记录和剩余买入委托 和 卖方交易记录 卖方更新委托
                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
                        $buyNum = $data['number'] - $result['balance']; //剩余买入量
                        $data3 = [ //买方 剩余买入委托 数据
                            'uid'=>$userBuy['uid'],
                            'phone'=>$userBuy['phone'],
                            'time'=>$resultBuy['time'],
                            //'time'=>time(),
                            'hour'=>time(),
                            //'hour'=>$resultBuy['time'],
                            'price'=>$data['price'],    //委托价格
                            //'deal'=>$result['price'],   //未一次成交完,成交价格未知
                            'number'=>$data['number'],  //委托数量(正好成交)
                            'balance'=>$buyNum,         //剩余委托数量
                            // 'status'=>'委托中'        //状态默认委托中
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);
                        if(! $result3){
                            $this->transResult('rollback','买入');
                        }

                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //买入部分交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$data['price'],    // 委托价格
                            'tradePrice'=>$result['price'],     // 交易价格
                            'number'=>$result['balance'],       // 买入数量(卖方剩余卖出量)
                            'sid'=>$userSell['phone'],          // 卖方uid
                            'sphone'=>$userSell['phone'],       // 卖方phone
                            'delegate_id'=>$result['sign']      // 卖方的委托编号
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);
                        if(! $result4){
                            $this->transResult('rollback','买入');
                        }

                        //找出刚刚的买家买入后的剩余委托记录
                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','1',$userBuy['phone']) -> order('hour desc') -> find();
                        $data5 = [ //卖方交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$result['price'],  //委托价格
                            'tradePrice'=>$result['price'],     //卖出价格
                            'number'=>$result['balance'],          //卖出数量(卖出剩余数量)
                            'sid'=>$userBuy['uid'],             //buy方uid
                            'sphone'=>$userBuy['phone'],        //buy方phone
                            'type'  => '卖出',
                            'fee'  => $fee,
                            'delegate_id'=> $delegateBuy['sign']// 记录买家刚刚买入历史委托记录的委托号
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);
                        if(!$result5){
                            $this->transResult('rollback','买入');
                        }

                        $data6 = [ //卖方更新委托记录
                            'hour'=>$resultSell['time'],
                            //'balance'=>0, // 全部卖出 默认0
                            'status'=>'已成交',
                        ] ;
                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
                        if(! $result6){
                            $this->transResult('rollback','买入');
                        }

                        $this->transResult('commit','买入');

                    }
                    else{
                        $this->transResult('rollback','买入');
                    }
                }
            }
            else{
                M()->rollback(); //事务回滚,关闭事务
                // 没有触发买入成交条件, 进入买入委托
                if($method == 'ajax'){
                    $this->ajaxResult(1,'此条买入继续委托!');
                }
                $data = [ //买入委托数据
                    'uid'=>$phone,
                    'phone'=>$phone,
                    'time'=>time(),
                    'hour'=>time(),
                    'price'=>$data['price'],    //委托价格
                    //'deal'=>$result['price'],   //交易价格
                    'number'=>$data['number'],  //委托数量
                    'balance'=>$data['number'], //剩余委托数量
                    // 'status'=>'委托中' ,       //状态默认 委托中
                    // 'type'=>'买入'        //类型默认 买入
                ] ;
                $result3 = D('delegate') -> addOrder($data);
                if($result3){
                    // return [ 'error' => 0,'message'  => '买入委托成功!' ];
                    $this->ajaxResult(0,'买入委托成功!');
                }else{
                    // return [ 'error' => 1,'message'  => '买入委托失败!' ];
                    $this->ajaxResult(1,'买入委托失败!');
                }
            }
        }
        else{
            // 取卖家用户的 卖出价格 和 卖出数量
            $data['price'] = $price;   // 卖出价格
            $data['number'] = $number; // 卖出量
            // 开启事务,处理交易

            M()->startTrans();

            // 查找买方中(买入价 >= 卖出价)买价最高,时间最早的一条买入记录 剩余买入量不为 0
            $result = D('Delegate') -> findBuy($phone,$data['price']); //买方委托记录

            // 买方委托中有一条 则可触发交易 (出价最高,时间最早,以买方价成交)
            if($result){

                //进行交易处理,获取 卖方/买方 用户信息
                $userSell = D('User') -> findUser($phone);
                $userBuy = D('User') -> findUser($result['phone']);

                // 卖出量 = 剩余买入量
                if($data['number'] == $result['balance']){

                    //卖家卖出对应数量
                    $userSell['number'] =  $userSell['numcoin'] - $data['number'];
                    $userSell['account'] =  $userSell['account'] + $data['number']*$result['price']*0.997;
                    $fee = $data['number']*$result['price']*0.003;
                    $data1 = [
                        'numCoin'=>$userSell['number'],
                        'account'=>$userSell['account']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);

                    //买家买入对应数量
                    $userBuy['number'] = $userBuy['numcoin'] + $data['number'];
                    $userBuy['account'] = $userBuy['account'] - $data['number']*$result['price'];
                    $data2 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 卖方历史委托 卖方交易记录 和 买方交易记录 买方更新委托记录
                        $data3 = [ //卖方 历史委托 数据
                            'uid'=>$userSell['uid'],
                            'phone'=>$userSell['phone'],
                            'time'=>$resultSell['time'],
                            'hour'=>$resultSell['time'],
                            'price'=>$data['price'],    //委托价格
                            'deal'=>$result['price'],     //以买方价格成交 = 成交价格
                            'number'=>$data['number'],  //委托数量(正好成交)
                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
                            'status'=>'已成交'
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);
                        if(!$result3){
                            $this->transResult('rollback','卖出');
                        }
                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //卖方交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$data['price'],        // 委托价格
                            'tradePrice'=>$result['price'],           // 卖出价格(以买方家成交)
                            'number'=>$data['number'],              // 卖出数量(正好成交)
                            'sid'=>$userBuy['phone'],               // 买方uid
                            'sphone'=>$userBuy['phone'],            // 买方phone
                            'delegate_id'=>$result['sign'],         // 买方的委托id号
                            'type'  => '卖出',
                            'fee' => $fee
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);
                        if(!$result4){
                            $this->transResult('rollback','卖出');
                        }
                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','2',$userSell['phone']) -> order('hour desc') -> find();

                        $data5 = [ //买方交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$result['price'],  //委托价格
                            'tradePrice'=>$result['price'],       //买入价格(以买方价成交)
                            'number'=>$data['number'],          //买入数量(正好成交)
                            'sid'=>$userSell['uid'],            //sell方uid
                            'sphone'=>$userSell['phone'],       //sell方phone
                            //'type'  => '买入',
                            'delegate_id'=> $delegateSell['sign']// 记录卖方的委托号
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);
                        if(!$result5){
                            $this->transResult('rollback','卖出');
                        }
                        $data6 = [ //买方 更新 委托记录
                            'hour'=>$resultBuy['time'],
                            //'balance'=>0,
                            'status'=>'已成交'
                        ];
                        $result6 = D('delegate') -> updateOrder($userBuy['phone'],$data6);
                        if(!$result6){
                            $this->transResult('rollback','卖出');
                        }

                        $this->transResult('commit','卖出');

                    }
                    else{
                        $this->transResult('rollback','卖出');
                    }
                }
                // 卖出量 < 剩余买入量
                elseif ($data['number'] < $result['balance']){

                    //卖出方足量卖出
                    $userSell['number'] =  $userSell['numcoin'] - $data['number'];
                    $userSell['account'] =  $userSell['account'] + $data['number']*$result['price']*0.997;
                    $fee = $data['number']*$result['price']*0.003;
                    $data1 = [
                        'numCoin'=>$userSell['number'],
                        'account'=>$userSell['account']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);

                    //买入方买入 卖出的数量 剩余买入量继续委托
                    $userBuy['number'] = $userBuy['numcoin'] + $data['number'];
                    $userBuy['account'] = $userBuy['account'] - $data['number']*$result['price'];

                    $data2 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 卖方历史委托 卖方交易记录 和 买方交易记录 买方更新委托记录
                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
                        $data3 = [ //卖方历史 委托 数据
                            'uid'=>$userSell['uid'],
                            'phone'=>$userSell['phone'],
                            'time'=>$resultSell['time'],
                            'hour'=>$resultSell['time'],
                            'price'=>$data['price'],    //委托价格
                            'deal'=>$result['price'],     //以买方价格成交 = 成交价格
                            'number'=>$data['number'],  //委托数量(正好成交)
                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
                            'status'=>'已成交'
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);
                        if(!$result3){
                            $this->transResult('rollback','卖出');
                        }

                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //卖方交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$data['price'],        // 委托价格
                            'tradePrice'=>$result['price'],           // 卖出价格(以买方家成交)
                            'number'=>$data['number'],              // 卖出数量(正好成交)
                            'sid'=>$userBuy['phone'],               // 买方uid
                            'sphone'=>$userBuy['phone'],            // 买方phone
                            'delegate_id'=>$result['sign'],         // 买方的委托id号
                            'type'  => '卖出',
                            'fee'   =>  $fee
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);
                        if(!$result4){
                            $this->transResult('rollback','卖出');
                        }

                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','2',$userSell['phone']) -> order('hour desc') -> find();

                        $data5 = [ //买方交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$result['price'],  //委托价格
                            'tradePrice'=>$result['price'],       //买入价格(以买方价成交)
                            'number'=>$data['number'],          //买入数量(卖方卖出数量,剩余继续委托)
                            'sid'=>$userSell['uid'],             //sell方uid
                            'sphone'=>$userSell['phone'],        //sell方phone
                            //'type'  => '买入',
                            'delegate_id'=> $delegateSell['sign']// 记录卖方的委托号
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);
                        if(!$result5){
                            $this->transResult('rollback','卖出');
                        }

                        $resultNum = $result['balance'] - $data['number']; //买方剩余委托量
                        $data6 = [ //买方更新委托记录 剩余继续委托--委托中
                            'hour'=>$resultBuy['time'],
                            'balance'=>$resultNum,
                            //'status'=>'委托中',
                        ] ;
                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
                        if(!$result6){
                            $this->transResult('rollback','卖出');
                        }

                        $this->transResult('commit','卖出');

                    }else{
                        $this->transResult('rollback','卖出');
                    }
                }
                // 卖出量 > 剩余买入量
                elseif ($data['number'] > $result['balance']){

                    // 卖出 剩余买入的量 剩余卖出量进入卖出委托
                    $userSell['number'] =  $userSell['numcoin'] - $result['balance'];
                    $userSell['account'] =  $userSell['account'] + $result['balance']*$result['price']*0.997;
                    $fee = $result['balance']*$result['price']*0.003;
                    $data1 = [
                        'numCoin'=>$userSell['number'],
                        'account'=>$userSell['account']
                    ];
                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);

                    // 买入方足量买入
                    $userBuy['number'] = $userBuy['numcoin'] + $result['balance'];
                    $userBuy['account'] = $userBuy['account'] - $result['balance']*$result['price'];
                    $data2 = [
                        'numCoin'=>$userBuy['number'],
                        'account'=>$userBuy['account']
                    ];
                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);

                    if(!$resultBuy['error'] && !$resultSell['error']){

                        // 交易成功,分别记录 买方交易记录和剩余买入委托 和 卖方交易记录 卖方更新委托
                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
                        $sellNum = $data['number'] - $result['balance']; //剩余卖出量
                        $data3 = [ //卖出方 剩余卖出委托数据
                            'uid'=>$userSell['uid'],
                            'phone'=>$userSell['phone'],
                            'time'=>$resultSell['time'],
                            //'time'=>time(),
                            'hour'=>time(),
                            //'hour'=>$resultBuy['time'],
                            'price'=>$data['price'],    //委托价格
                            //'deal'=>$result['price'],   //未一次成交完,成交价格未知
                            'number'=>$data['number'],  //委托数量
                            'balance'=>$sellNum,         //剩余委托数量
                            // 'status'=>'委托中'        //状态默认委托中
                            'type'=>'卖出'
                        ] ;
                        $result3 = D('delegate') -> addOrder($data3);
                        if(!$result3){
                            $this->transResult('rollback','卖出');
                        }

                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
                        $data4 = [ //卖出部分交易数据
                            'phone'=>$userSell['phone'],
                            'uid'=>$userSell['uid'],
                            'time'=>$resultSell['time'],
                            'delegatePrice'=>$data['price'],    // 委托价格
                            'tradePrice'=>$result['price'],     // 卖出价格(以买方价格成交,出价高者,时间早着先得)
                            'number'=>$result['balance'],       // 卖出数量(买方剩余买入量)
                            'sid'=>$userBuy['uid'],          // buy方uid
                            'sphone'=>$userBuy['phone'],       // buy方phone
                            'delegate_id'=>$result['sign'],      // buy方的委托编号
                            'type'=>'卖出',
                            'fee'=>$fee
                        ] ;
                        $result4 = D('Trade') -> addTrade($data4);
                        if(!$result4){
                            $this->transResult('rollback','卖出');
                        }

                        //找出卖家卖出的剩余委托记录
                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','1',$userSell['phone']) -> order('hour desc') -> find();
                        $data5 = [ //买方交易数据
                            'phone'=>$userBuy['phone'],
                            'uid'=>$userBuy['uid'],
                            'time'=>$resultBuy['time'],
                            'delegatePrice'=>$result['price'],  //委托价格
                            'tradePrice'=>$result['price'],     //买入价格
                            'number'=>$result['balance'],        //买入数量(买家买入剩余数量)
                            'sid'=>$userSell['uid'],             //sell方uid
                            'sphone'=>$userSell['phone'],        //sell方phone
                            //'type'  => '买入',
                            'delegate_id'=> $delegateSell['sign']// 记录卖家刚刚卖出部分后委托记录的委托号
                        ] ;
                        $result5 = D('Trade') -> addTrade($data5);
                        if(!$result5){
                            $this->transResult('rollback','卖出');
                        }

                        $data6 = [ //买方更新委托记录
                            'hour'=>$resultBuy['time'],
                            //'balance'=>0, // 全部卖出 默认0
                            'status'=>'已成交',
                        ] ;
                        $result6 = D('delegate') -> updateOrder($userBuy['phone'],$data6);
                        if(!$result6){
                            $this->transResult('rollback','卖出');
                        }

                        $this->transResult('commit','卖出');

                    }else{
                        $this->transResult('rollback','卖出');
                    }
                }
            }
            // 没有卖出成交条件, 进入卖出委托
            else{
                M()->rollback(); //事务回滚,关闭事务
                if($method == 'ajax'){
                    $this->ajaxResult(1,'此条卖出继续委托!');
                }
                $data = [ //卖出委托数据
                    'uid'=>$phone,
                    'phone'=>$phone,
                    'time'=>time(),
                    'hour'=>time(),
                    'price'=>$data['price'],    //委托价格
                    //'deal'=>$result['price'],   //交易价格
                    'number'=>$data['number'],  //委托数量
                    'balance'=>$data['number'], //剩余委托数量
                    // 'status'=>'委托中'        //状态默认委托中
                    'type'=>'卖出'        //状态默认委托中
                ] ;
                if(D('delegate') -> addOrder($data)){
                    $this->ajaxResult(0,'卖出委托成功!');
                }else{
                    $this->ajaxResult(1,'卖出委托失败!');
                }
            }
        }
    }
//    public function buyAction()
//    {
//        if(IS_POST){
//            // 取买家用户信息
//            $phone = session('phone');
//
//            if(!$phone){
//                $this->ajaxResult(1,'未登录用户禁止访问!');
//            }
//
//            //接收用户的购买价格和购买数量
//            $data['price'] = I('post.price'); // 买入价格
//            $data['number'] = I('post.number'); // 买入量
//
//            M()->startTrans();
//            // 查找卖方中(卖出价 <= 买入价)卖价最低,时间最早的一条卖出记录,并且 剩余卖出量 不为 0
//            $result = D('Delegate') -> findSell($phone,$data['price']);
//
//            // 卖方委托中有一条 则可触发交易
//            if($result){
//
//                //获取买方/卖方信息,进行交易处理
//                $userBuy = D('User') -> findUser($phone);
//                $userSell = D('User') -> findUser($result['phone']);
//
//                // 买入量 = 卖方 剩余卖出量
//                if($data['number'] == $result['balance']){
//
//                    //买家买入对应数量
//                    $userBuy['number'] =  $userBuy['numcoin'] + $data['number'];
//                    $userBuy['account'] =  $userBuy['account'] - $data['number']*$result['price'];
//                    $data1 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);
//
//                    //卖家卖出对应数量
//                    $userSell['number'] = $userSell['numcoin'] - $data['number'];
//                    $userSell['account'] = $userSell['account'] + $data['number']*$result['price']*0.997;
//                    $fee = $data['number']*$result['price']*0.003;// 0.3%的手续费,先放这里
//                    $data2 = [
//                        'numCoin'=>$userSell['number'],
//                        'account'=>$userSell['account']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 买方历史委托 买方交易记录 和 卖方交易记录
//                        // 历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
//                        $data3 = [ //买方 历史 委托 数据
//                            'uid'=>$userBuy['uid'],
//                            'phone'=>$userBuy['phone'],
//                            'time'=>$resultBuy['time'],
//                            'hour'=>$resultBuy['time'],
//                            'price'=>$data['price'], //委托价格
//                            'deal'=>$result['price'], //以卖方价格成交 = 成交价格
//                            'number'=>$data['number'], //委托数量(正好成交)
//                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
//                            'status'=>'已成交'
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//
//                        if(! $result3){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //买方交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$data['price'],    // 委托价格
//                            'tradePrice'=>$result['price'],     // 买入价格
//                            'number'=>$data['number'],          // 买入数量(正好成交)
//                            'sid'=>$userSell['phone'],          // 卖方uid
//                            'sphone'=>$userSell['phone'],       // 卖方phone
//                            'delegate_id'=>$result['sign']        // 卖家的委托id号
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//
//                        if(! $result4){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','2',$userBuy['phone']) -> order('hour desc') -> find();
//                        $data5 = [ //卖方交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$result['price'], //委托价格
//                            'tradePrice'=>$result['price'], //卖出价格
//                            'number'=>$data['number'], //卖出数量(正好成交)
//                            'sid'=>$userBuy['uid'], //buy方uid
//                            'sphone'=>$userBuy['phone'], //buy方phone
//                            'type'  => '卖出',
//                            'delegate_id'=> $delegateBuy['sign'],// 记录买方的委托号
//                            'fee'=>$fee
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//
//                        if(!$result5){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $data6 = [ //卖方 更新 委托记录
//                            'hour'=>$resultSell['time'],
//                            //'balance'=>0,
//                            'status'=>'已成交'
//                        ];
//                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
//
//                        if(! $result6){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $this->transResult('commit','买入');
//
//                    }
//                    else{
//                        $this->transResult('rollback','买入');
//                    }
//                }
//                // 买入量 < 卖出 (买方买入量 < 卖方剩余卖出量)
//                elseif ($data['number'] < $result['balance']){
//
//                    // 买入方 足量 买入
//                    $userBuy['number'] =  $userBuy['numcoin'] + $data['number'];
//                    $userBuy['account'] =  $userBuy['account'] - $data['number']*$result['price'];
//                    $data1 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);
//
//                    //卖出方 卖出 买入的数量 剩余卖出量继续委托
//                    $userSell['number'] = $userSell['numcoin'] - $data['number'];
//                    $userSell['account'] = $userSell['account'] - $data['number']*$result['price']*0.997;
//                    $fee = $data['number']*$result['price']*0.003;
//                    $data2 = [
//                        'numCoin'=>$userSell['number'],
//                        'account'=>$userSell['account']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 买方历史委托 买方交易记录 和 买方交易记录 卖方继续委托
//                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
//                        $data3 = [ //买方历史委托数据
//                            'uid'=>$userBuy['uid'],
//                            'phone'=>$userBuy['phone'],
//                            'time'=>$resultBuy['time'],
//                            'hour'=>$resultBuy['time'],
//                            'price'=>$data['price'], //委托价格
//                            'deal'=>$result['price'], //以卖方价格成交 = 成交价格
//                            'number'=>$data['number'], //委托数量(正好成交)
//                            //'balance'=>$data['number'], //剩余委托数量(正好成交,默认0)
//                            'status'=>'已成交'
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//                        if(! $result3){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //买方交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$data['price'],    // 委托价格
//                            'tradePrice'=>$result['price'],     // 买入价格
//                            'number'=>$data['number'],          // 买入数量(正好成交)
//                            'sid'=>$userSell['phone'],          // 卖方uid
//                            'sphone'=>$userSell['phone'],       // 卖方phone
//                            'delegate_id'=>$result['sign']      // 卖家的委托编号
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//                        if(! $result4){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        //找出刚刚的买家买入历史委托记录
//                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','2',$userBuy['phone']) -> order('hour desc') -> find();
//                        $data5 = [ //卖方交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$result['price'], //委托价格
//                            'tradePrice'=>$result['price'], //卖出价格
//                            'number'=>$data['number'], //卖出数量(买家买入数量)
//                            'sid'=>$userBuy['uid'], //buy方uid
//                            'sphone'=>$userBuy['phone'], //buy方phone
//                            'type'  => '卖出',
//                            'fee'  => $fee,
//                            'delegate_id'=> $delegateBuy['sign']// 记录买家刚刚买入历史委托记录的委托号
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//                        if(!$result5){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $resultNum = $result['balance'] - $data['number']; // 卖方 剩余 委托量
//                        $data6 = [ //卖方更新委托记录 剩余继续委托--委托中
//                            'hour'=>$resultSell['time'],
//                            'balance'=>$resultNum,
//                            //'status'=>'委托中',
//                        ] ;
//                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
//                        if(! $result6){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $this->transResult('commit','买入');
//
//                    }
//                    else{
//                        $this->transResult('rollback','买入');
//                    }
//                }
//                // 买入量 > 卖出量 (买方买入量 > 卖方剩余卖出量)
//                elseif ($data['number'] > $result['balance']){
//
//                    // 买入 卖出的数量 剩余买入量进入委托中
//                    $userBuy['number'] =  $userBuy['numcoin'] + $result['balance'];
//                    $userBuy['account'] = $userBuy['account'] - $result['balance']*$result['price'];
//                    $data1 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data1);
//
//                    // 卖出方足量卖出剩余量
//                    $userSell['number'] = $userSell['numcoin'] - $result['balance'];
//                    $userSell['account'] = $userSell['account'] + $result['balance']*$result['price']*0.997;
//                    $fee = $result['balance']*$result['price']*0.003;
//                    $data2 = [
//                        'numCoin'=>$userSell['number']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 买方交易记录和剩余买入委托 和 卖方交易记录 卖方更新委托
//                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
//                        $buyNum = $data['number'] - $result['balance']; //剩余买入量
//                        $data3 = [ //买方 剩余买入委托 数据
//                            'uid'=>$userBuy['uid'],
//                            'phone'=>$userBuy['phone'],
//                            'time'=>$resultBuy['time'],
//                            //'time'=>time(),
//                            'hour'=>time(),
//                            //'hour'=>$resultBuy['time'],
//                            'price'=>$data['price'],    //委托价格
//                            //'deal'=>$result['price'],   //未一次成交完,成交价格未知
//                            'number'=>$data['number'],  //委托数量(正好成交)
//                            'balance'=>$buyNum,         //剩余委托数量
//                            // 'status'=>'委托中'        //状态默认委托中
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//                        if(! $result3){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //买入部分交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$data['price'],    // 委托价格
//                            'tradePrice'=>$result['price'],     // 交易价格
//                            'number'=>$result['balance'],       // 买入数量(卖方剩余卖出量)
//                            'sid'=>$userSell['phone'],          // 卖方uid
//                            'sphone'=>$userSell['phone'],       // 卖方phone
//                            'delegate_id'=>$result['sign']      // 卖方的委托编号
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//                        if(! $result4){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        //找出刚刚的买家买入后的剩余委托记录
//                        $delegateBuy = M('Delegate')-> where('status=%s and phone=%s','1',$userBuy['phone']) -> order('hour desc') -> find();
//                        $data5 = [ //卖方交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$result['price'],  //委托价格
//                            'tradePrice'=>$result['price'],     //卖出价格
//                            'number'=>$result['balance'],          //卖出数量(卖出剩余数量)
//                            'sid'=>$userBuy['uid'],             //buy方uid
//                            'sphone'=>$userBuy['phone'],        //buy方phone
//                            'type'  => '卖出',
//                            'fee'  => $fee,
//                            'delegate_id'=> $delegateBuy['sign']// 记录买家刚刚买入历史委托记录的委托号
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//                        if(!$result5){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $data6 = [ //卖方更新委托记录
//                            'hour'=>$resultSell['time'],
//                            //'balance'=>0, // 全部卖出 默认0
//                            'status'=>'已成交',
//                        ] ;
//                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
//                        if(! $result6){
//                            $this->transResult('rollback','买入');
//                        }
//
//                        $this->transResult('commit','买入');
//
//                    }
//                    else{
//                        $this->transResult('rollback','买入');
//                    }
//                }
//            }
//            else{
//                M()->rollback(); //事务回滚,关闭事务
//                // 没有触发买入成交条件, 进入买入委托
//                $data = [ //买入委托数据
//                    'uid'=>$phone,
//                    'phone'=>$phone,
//                    'time'=>time(),
//                    'hour'=>time(),
//                    'price'=>$data['price'],    //委托价格
//                    //'deal'=>$result['price'],   //交易价格
//                    'number'=>$data['number'],  //委托数量
//                    'balance'=>$data['number'], //剩余委托数量
//                    // 'status'=>'委托中' ,       //状态默认 委托中
//                    // 'type'=>'买入'        //类型默认 买入
//                ] ;
//                $result3 = D('delegate') -> addOrder($data);
//                if($result3){
//
//                    $this->ajaxResult(0,'买入委托成功!');
//                }else{
//
//                    $this->ajaxResult(1,'买入委托失败!');
//                }
//            }
//
//        }else{
//            $data['error'] = 1;
//            $data['message'] = '禁止访问!';
//            $this->ajaxReturn($data);
//        }
//    }

//    public function sellAction()
//    {
//        if(IS_POST){
//            // 取卖家用户信息
//            $phone = session('phone');
//
//            if(!$phone){
//                $this->ajaxResult(1,'未登录用户禁止访问!');
//            }
//
//            //接收卖家用户的 卖出价格 和 卖出数量
//            $data['price'] = I('post.price');   // 卖出价格
//            $data['number'] = I('post.number'); // 卖出量
//
//            // 开启事务,处理交易
//            M()->startTrans();
//            // 查找买方中(买入价 >= 卖出价)买价最高,时间最早的一条买入记录 剩余买入量不为 0
//            $result = D('Delegate') -> findBuy($phone,$data['price']); //买方委托记录
//
//            // 买方委托中有一条 则可触发交易 (出价最高,时间最早,以买方价成交)
//            if($result){
//
//                //进行交易处理,获取 卖方/买方 用户信息
//                $userSell = D('User') -> findUser($phone);
//                $userBuy = D('User') -> findUser($result['phone']);
//
//                // 卖出量 = 剩余买入量
//                if($data['number'] == $result['balance']){
//
//                    //卖家卖出对应数量
//                    $userSell['number'] =  $userSell['numcoin'] - $data['number'];
//                    $userSell['account'] =  $userSell['account'] + $data['number']*$result['price']*0.997;
//                    $fee = $data['number']*$result['price']*0.003;
//                    $data1 = [
//                        'numCoin'=>$userSell['number'],
//                        'account'=>$userSell['account']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);
//
//                    //买家买入对应数量
//                    $userBuy['number'] = $userBuy['numcoin'] + $data['number'];
//                    $userBuy['account'] = $userBuy['account'] - $data['number']*$result['price'];
//                    $data2 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 卖方历史委托 卖方交易记录 和 买方交易记录 买方更新委托记录
//                        $data3 = [ //卖方 历史委托 数据
//                            'uid'=>$userSell['uid'],
//                            'phone'=>$userSell['phone'],
//                            'time'=>$resultSell['time'],
//                            'hour'=>$resultSell['time'],
//                            'price'=>$data['price'],    //委托价格
//                            'deal'=>$result['price'],     //以买方价格成交 = 成交价格
//                            'number'=>$data['number'],  //委托数量(正好成交)
//                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
//                            'status'=>'已成交'
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//                        if(!$result3){
//                            $this->transResult('rollback','卖出');
//                        }
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //卖方交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$data['price'],        // 委托价格
//                            'tradePrice'=>$result['price'],           // 卖出价格(以买方家成交)
//                            'number'=>$data['number'],              // 卖出数量(正好成交)
//                            'sid'=>$userBuy['phone'],               // 买方uid
//                            'sphone'=>$userBuy['phone'],            // 买方phone
//                            'delegate_id'=>$result['sign'],         // 买方的委托id号
//                            'type'  => '卖出',
//                            'fee' => $fee
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//                        if(!$result4){
//                            $this->transResult('rollback','卖出');
//                        }
//                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','2',$userSell['phone']) -> order('hour desc') -> find();
//
//                        $data5 = [ //买方交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$result['price'],  //委托价格
//                            'tradePrice'=>$result['price'],       //买入价格(以买方价成交)
//                            'number'=>$data['number'],          //买入数量(正好成交)
//                            'sid'=>$userSell['uid'],            //sell方uid
//                            'sphone'=>$userSell['phone'],       //sell方phone
//                            //'type'  => '买入',
//                            'delegate_id'=> $delegateSell['sign']// 记录卖方的委托号
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//                        if(!$result5){
//                            $this->transResult('rollback','卖出');
//                        }
//                        $data6 = [ //买方 更新 委托记录
//                            'hour'=>$resultBuy['time'],
//                            //'balance'=>0,
//                            'status'=>'已成交'
//                        ];
//                        $result6 = D('delegate') -> updateOrder($userBuy['phone'],$data6);
//                        if(!$result6){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $this->transResult('commit','卖出');
//
//                    }
//                    else{
//                        $this->transResult('rollback','卖出');
//                    }
//                }
//                // 卖出量 < 剩余买入量
//                elseif ($data['number'] < $result['balance']){
//
//                    //卖出方足量卖出
//                    $userSell['number'] =  $userSell['numcoin'] - $data['number'];
//                    $userSell['account'] =  $userSell['account'] + $data['number']*$result['price']*0.997;
//                    $fee = $data['number']*$result['price']*0.003;
//                    $data1 = [
//                        'numCoin'=>$userSell['number'],
//                        'account'=>$userSell['account']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);
//
//                    //买入方买入 卖出的数量 剩余买入量继续委托
//                    $userBuy['number'] = $userBuy['numcoin'] + $data['number'];
//                    $userBuy['account'] = $userBuy['account'] - $data['number']*$result['price'];
//
//                    $data2 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 卖方历史委托 卖方交易记录 和 买方交易记录 买方更新委托记录
//                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
//                        $data3 = [ //卖方历史 委托 数据
//                            'uid'=>$userSell['uid'],
//                            'phone'=>$userSell['phone'],
//                            'time'=>$resultSell['time'],
//                            'hour'=>$resultSell['time'],
//                            'price'=>$data['price'],    //委托价格
//                            'deal'=>$result['price'],     //以买方价格成交 = 成交价格
//                            'number'=>$data['number'],  //委托数量(正好成交)
//                            //'balance'=>$data['number'], //剩余委托数量(正好成交,无剩余量,默认0)
//                            'status'=>'已成交'
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//                        if(!$result3){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //卖方交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$data['price'],        // 委托价格
//                            'tradePrice'=>$result['price'],           // 卖出价格(以买方家成交)
//                            'number'=>$data['number'],              // 卖出数量(正好成交)
//                            'sid'=>$userBuy['phone'],               // 买方uid
//                            'sphone'=>$userBuy['phone'],            // 买方phone
//                            'delegate_id'=>$result['sign'],         // 买方的委托id号
//                            'type'  => '卖出',
//                            'fee'   =>  $fee
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//                        if(!$result4){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','2',$userSell['phone']) -> order('hour desc') -> find();
//
//                        $data5 = [ //买方交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$result['price'],  //委托价格
//                            'tradePrice'=>$result['price'],       //买入价格(以买方价成交)
//                            'number'=>$data['number'],          //买入数量(卖方卖出数量,剩余继续委托)
//                            'sid'=>$userSell['uid'],             //sell方uid
//                            'sphone'=>$userSell['phone'],        //sell方phone
//                            //'type'  => '买入',
//                            'delegate_id'=> $delegateSell['sign']// 记录卖方的委托号
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//                        if(!$result5){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $resultNum = $result['balance'] - $data['number']; //买方剩余委托量
//                        $data6 = [ //买方更新委托记录 剩余继续委托--委托中
//                            'hour'=>$resultBuy['time'],
//                            'balance'=>$resultNum,
//                            //'status'=>'委托中',
//                        ] ;
//                        $result6 = D('delegate') -> updateOrder($userSell['phone'],$data6);
//                        if(!$result6){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $this->transResult('commit','卖出');
//
//                    }else{
//                        $this->transResult('rollback','卖出');
//                    }
//                }
//                // 卖出量 > 剩余买入量
//                elseif ($data['number'] > $result['balance']){
//
//                    // 卖出 剩余买入的量 剩余卖出量进入卖出委托
//                    $userSell['number'] =  $userSell['numcoin'] - $result['balance'];
//                    $userSell['account'] =  $userSell['account'] + $result['balance']*$result['price']*0.997;
//                    $fee = $result['balance']*$result['price']*0.003;
//                    $data1 = [
//                        'numCoin'=>$userSell['number'],
//                        'account'=>$userSell['account']
//                    ];
//                    $resultSell = D('User') -> saveNum($userSell['phone'],$data1);
//
//                    // 买入方足量买入
//                    $userBuy['number'] = $userBuy['numcoin'] + $result['balance'];
//                    $userBuy['account'] = $userBuy['account'] - $result['balance']*$result['price'];
//                    $data2 = [
//                        'numCoin'=>$userBuy['number'],
//                        'account'=>$userBuy['account']
//                    ];
//                    $resultBuy = D('User') -> saveNum($userBuy['phone'],$data2);
//
//                    if(!$resultBuy['error'] && !$resultSell['error']){
//
//                        // 交易成功,分别记录 买方交易记录和剩余买入委托 和 卖方交易记录 卖方更新委托
//                        //历史委托列表显示：委托时间、类型（买入/卖出）、委托价格price、委托数number、平均成交价、成交量、成交额、状态
//                        $sellNum = $data['number'] - $result['balance']; //剩余卖出量
//                        $data3 = [ //卖出方 剩余卖出委托数据
//                            'uid'=>$userSell['uid'],
//                            'phone'=>$userSell['phone'],
//                            'time'=>$resultSell['time'],
//                            //'time'=>time(),
//                            'hour'=>time(),
//                            //'hour'=>$resultBuy['time'],
//                            'price'=>$data['price'],    //委托价格
//                            //'deal'=>$result['price'],   //未一次成交完,成交价格未知
//                            'number'=>$data['number'],  //委托数量
//                            'balance'=>$sellNum,         //剩余委托数量
//                            // 'status'=>'委托中'        //状态默认委托中
//                            'type'=>'卖出'
//                        ] ;
//                        $result3 = D('delegate') -> addOrder($data3);
//                        if(!$result3){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        // 交易表 列表显示：时间、类型（买入/卖出）、单价、数量、金额、手续费
//                        $data4 = [ //卖出部分交易数据
//                            'phone'=>$userSell['phone'],
//                            'uid'=>$userSell['uid'],
//                            'time'=>$resultSell['time'],
//                            'delegatePrice'=>$data['price'],    // 委托价格
//                            'tradePrice'=>$result['price'],     // 卖出价格(以买方价格成交,出价高者,时间早着先得)
//                            'number'=>$result['balance'],       // 卖出数量(买方剩余买入量)
//                            'sid'=>$userBuy['uid'],          // buy方uid
//                            'sphone'=>$userBuy['phone'],       // buy方phone
//                            'delegate_id'=>$result['sign'],      // buy方的委托编号
//                            'type'=>'卖出',
//                            'fee'=>$fee
//                        ] ;
//                        $result4 = D('Trade') -> addTrade($data4);
//                        if(!$result4){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        //找出卖家卖出的剩余委托记录
//                        $delegateSell = M('Delegate')-> where('status=%s and phone=%s','1',$userSell['phone']) -> order('hour desc') -> find();
//                        $data5 = [ //买方交易数据
//                            'phone'=>$userBuy['phone'],
//                            'uid'=>$userBuy['uid'],
//                            'time'=>$resultBuy['time'],
//                            'delegatePrice'=>$result['price'],  //委托价格
//                            'tradePrice'=>$result['price'],     //买入价格
//                            'number'=>$result['balance'],        //买入数量(买家买入剩余数量)
//                            'sid'=>$userSell['uid'],             //sell方uid
//                            'sphone'=>$userSell['phone'],        //sell方phone
//                            //'type'  => '买入',
//                            'delegate_id'=> $delegateSell['sign']// 记录卖家刚刚卖出部分后委托记录的委托号
//                        ] ;
//                        $result5 = D('Trade') -> addTrade($data5);
//                        if(!$result5){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $data6 = [ //买方更新委托记录
//                            'hour'=>$resultBuy['time'],
//                            //'balance'=>0, // 全部卖出 默认0
//                            'status'=>'已成交',
//                        ] ;
//                        $result6 = D('delegate') -> updateOrder($userBuy['phone'],$data6);
//                        if(!$result6){
//                            $this->transResult('rollback','卖出');
//                        }
//
//                        $this->transResult('commit','卖出');
//
//                    }else{
//                        $this->transResult('rollback','卖出');
//                    }
//                }
//            }
//            // 没有卖出成交条件, 进入卖出委托
//            else{
//                M()->rollback(); //事务回滚,关闭事务
//                $data = [ //卖出委托数据
//                    'uid'=>$phone,
//                    'phone'=>$phone,
//                    'time'=>time(),
//                    'hour'=>time(),
//                    'price'=>$data['price'],    //委托价格
//                    //'deal'=>$result['price'],   //交易价格
//                    'number'=>$data['number'],  //委托数量
//                    'balance'=>$data['number'], //剩余委托数量
//                    // 'status'=>'委托中'        //状态默认委托中
//                    'type'=>'卖出'        //状态默认委托中
//                ] ;
//                if(D('delegate') -> addOrder($data)){
//                    $this->ajaxResult(0,'卖出委托成功!');
//                }else{
//                    $this->ajaxResult(1,'卖出委托失败!');
//                }
//            }
//
//        }else{
//            $data['error'] = 1;
//            $data['message'] = '禁止访问!';
//            $this->ajaxReturn($data);
//        }
//    }

    // 买币(委托)操作
    public function buyAction()
    {
        if(IS_POST){
            // 取买家用户信息
            $phone = session('phone');

            if(!$phone){
                $this->ajaxResult(1,'未登录用户禁止访问!');
            }

            $this->trade($phone,I('post.number'),I('post.price'),'买入');

        }else{
            $data['error'] = 1;
            $data['message'] = '禁止访问!';
            $this->ajaxReturn($data);
        }
    }

    // 卖币(委托)操作
    public function sellAction()
    {
        if(IS_POST){
            // 取卖家用户信息
            $phone = session('phone');

            if(!$phone){
                $this->ajaxResult(1,'未登录用户禁止访问!');
            }

            $this->trade($phone,I('post.number'),I('post.price'),'卖出');

        }else{
            $data['error'] = 1;
            $data['message'] = '禁止访问!';
            $this->ajaxReturn($data);
        }
    }

    // 取消委托订单
    public function cancelOrderAction()
    {
        if(IS_POST){

        }else{

            $id = I('get.id');
            $data['id'] = $id;
            $data['status'] = '取消委托';
            $result = M('Delegate') -> data($data) -> save();
            if($result){
                $data['error'] = 0;
                $data['message'] = '取消委托成功!'.$id;
                $this->ajaxReturn($data);
            }else{
                $data['error'] = 1;
                $data['message'] = '取消委托失败!'.$id;
                $this->ajaxReturn($data);
            }
        }
    }

    // 历史委托列表(包括已成交委托和取消委托的记录)
    public function oldOrderAction()
    {
        $map['status'] = array('NEQ','委托中');
        $oldResult = M('delegate')->where($map)-> select();
        $this->assign('oldResult',$oldResult);
        $this->display('index');
    }

    // 买币时验证资金密码
    public function checkPwdAction()
    {

        if(IS_POST){

            if(session('phone')){

                $moneyPwd = I('post.moneyPwd');

                $result = M('user') -> where('phone=%s',['%s'=>session('phone')]) ->find();

                if($result){
                    if($result['moneypwd'] != md5($moneyPwd.$result['salt']).$result['salt']){
                        $data['error'] = 1;
                        $data['message'] = '资金密码错误!';
                        $this->ajaxReturn($data);
                    }else{
                        $data['error'] = 0;
                        $data['message'] = '资金密码正确!';
                        $this->ajaxReturn($data);
                    }
                }else{
                    $data['error'] = 1;
                    $data['message'] = '此用户不存在!';
                    $this->ajaxReturn($data);
                }

            }else{

                $data['error'] = 1;
                $data['message'] = '此用户未登录!';
                $this->ajaxReturn($data);
            }

        }else{
            $data['error'] = 1;
            $data['message'] = '禁止访问!';
            $this->ajaxReturn($data);
        }
    }

    public function dealAction()
    {
        // 取用户信息
        $phone = session('phone');
        $user = M('User') -> where('phone=%s',$phone)->find();
        $delegate = M('delegate')->where('phone=%s and status=%s',[$phone,'1'])->order('time desc')->select();

    }


}