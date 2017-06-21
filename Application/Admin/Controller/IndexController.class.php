<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {

    public function indexAction(){
////        phpinfo();
//        S(array(
//                'type'=>'memcache',
////                'type'=>'redis',
//                'host'=>'127.0.0.1',
//                'port'=>'11211',
////                'port'=>'6379',
//                'prefix'=>'think',
//                'expire'=>60)
//        );
//        S('test','hello Memcached');
//        echo S('test').'ok';
//        $redis = new \Redis();
//        $redis->connect('127.0.0.1',6379);
//        $redis->set('test','hello redis');
//        echo $redis->get('test');
        if(IS_POST){

        }else{
            $this->display();
        }
    }


}