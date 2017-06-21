<?php
return array(
	//'配置项'=>'配置值'
    'URL_ROUTER_ON' => true, // 开启自定义路由
    'URL_ROUTE_RULES'   => [
        // 轩辕币管理
        'coin'  =>  'Coin/coin',
        // 用户管理
        'user'  =>  'User/user',
        'audit'  =>  'User/audit',
        'loginRecord'  =>  'User/loginRecord',
        // 交易管理
        'currentDelegate'  =>  'Trade/currentDelegate',
        'historyDelegate'  =>  'Trade/historyDelegate',
        'tradeRecord'  =>  'Trade/tradeRecord',
        // 会员管理
        'cMember'  =>  'Member/cMember',
        'sMember'  =>  'Member/sMember',
        // 财务管理
        'tb'  =>  'Finance/tb',
        'tx'  =>  'Finance/tx',
        'cz'  =>  'Finance/cz',
        'wb'  =>  'Finance/wb',
        'zm'  =>  'Finance/zm',
        //报表中心
        'day'   =>  'Report/day',
        'week'   =>  'Report/week',
        'month'   =>  'Report/month',
        // 栏目管理
        'listColumn'    =>  'Column/list',
        'addColumn'    =>  'Column/add',
        'editColumn'    =>  'Column/edit',
        // 文章管理
        'listArticle'    =>  'Article/list',
        'addArticle'    =>  'Article/add',
        'editArticle'    =>  'Article/edit',
        // 系统管理
        'sites'     =>  'Setting/sites',
    ],

);