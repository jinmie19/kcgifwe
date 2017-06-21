<?php
return array(
	//'配置项'=>'配置值'


    'URL_ROUTER_ON' => true, // 开启自定义路由
    'URL_ROUTE_RULES'   => [ // 自定义路由规则
        //'coin'  =>  'Index/coin',
        'insert'  =>  'Index/insert',
        'doVerify'  =>  'Index/doVerify',
        'login'     =>  'Login/login',//登录
        'loginOut'     =>  'Login/loginOut',//注销
        'register'  =>  'Login/register',//注册
        'seekPass'=>'Login/seekPass',//找回密码

        'verify'  =>  'Login/verify',//图形验证码
        'sendMessage'=>'Login/sendMessage',//发送手机验证码
        'upload'=>'User/upload',//图片上传
        'trade' => 'Trade/index', //交易中心首页
        'buy' => 'Trade/buy',//交易中心--买比委托操作
        'checkPwd' => 'Trade/checkPwd', //交易中心--买币时验证资金密码
        'basicInformation'=>'User/basicInformation',//个人资料首页
        'bindEmail'=>'User/bindEmail',//邮箱绑定
        'sendEmail'=>'User/sendEmail',//邮件发送
        'modifyPhone'=>'User/modifyPhone',//手机号码重置
        'modifyPwd'=>'User/modifyPwd',//登录密码重置
        'modifyMoneyPwd'=>'User/modifyMoneyPwd',//资金密码设置
        'realName'=>'User/realName',//实名认证
        'place'=>'User/place',//收货地址绑定
        'sell' => 'Trade/sell', //交易中心--卖币委托操作
        'cancelOrder' => 'Trade/cancelOrder', //交易中心--取消委托操作
        'oldOrder' => 'Trade/oldOrder', //交易中心--历史委托

        'category'=>'Category/index',//栏目管理首页
        'cateUpdate'=>'Category/update',//栏目更新
        'cateAdd'=>'Category/add',//栏目添加
        'cateDelete'=>'Category/delete',//栏目删除
        'cateUp'=>'Category/up',//栏目上升
        'cateDown'=>'Category/down',//栏目下移
        'article'=>'Article/index',//文章首页


        'artAdd'=>'Article/add',//文章添加
        'artUpdate'=>'Article/update',//文章编辑
        'artDelete'=>'Article/delete',//文章删除
        'artUp'=>'Article/up',//文章上移
        'artDown'=>'Article/down',//文章下移

        'signRand'=>'Trade/signRand',

    ],



);