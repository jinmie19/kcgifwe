<?php
//Application/Common/Conf/db_server.php
//Application/Common/Conf/sql.sql
return array(
	//'配置项'=>'配置值'
    'ACTION_SUFFIX' => 'Action',    //动作后缀
    'SHOW_PAGE_TRACE'   =>  true,   //展示页面Trace(追踪)
    'URL_MODEL'         =>  2,  //URL模式
    'DEFAULT_MODULE'    =>  'Home',  // 默认模块
//    'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
//    'DEFAULT_ACTION'        =>  'index', // 默认操作名称
    'DEFAULT_FILTER'    =>  '', //取消默认的过滤器


    //加载扩展配置
    'LOAD_EXT_CONFIG'   =>  'db_server',
    'SESSION_AUTO_START'    =>  true,    // 是否自动开启Session
    'LOG_RECORD'            =>  true,   // 默认记录日志


);