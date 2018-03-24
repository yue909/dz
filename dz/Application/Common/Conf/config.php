<?php
    //这里填入的你域名
    define("_URL_","http://wap.weiyouxinchuang.com");
    //OSS ACCESS_KEY_ID
    define('_OSS_ACCESS_KEY_ID_', 'LTAIiKlgJ00qYBsV');
    //OSS ACCESS_KEY_SECRET
    define('_OSS_ACCESS_KEY_SECRET_', 'X1FIHFkSypveejFEbk5xcS9wNEAylM');
    //OSS Endpoint
    define('_OSS_ENDPOINT_', 'oss-cn-shenzhen.aliyuncs.com');
    //OSS Bucket
    define('_OSS_BUCKET_', 'new-weizhuan');

   return array (
	//配置项 => 配置值
  	// 'DB_TYPE'               =>  'mysql',     // 数据库类型
   //  'DB_HOST'               =>  '5784601d2e352.gz.cdb.myqcloud.com', // 服务器地址
   //  'DB_NAME'               =>  'douzhuan',          // 数据库名
   //  'DB_USER'               =>  'douzhuan',      // 用户名
   //  'DB_PWD'                =>  'aaa123456',          // 密码
   //  'DB_PORT'               =>  '10242',        // 端口
   //  'DB_PREFIX'             =>  'dz_',       // 数据表前缀
   //  'DB_CHARSET'            =>  'utf8',     // 网站编码

    
    // 线上数据库
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'douzhuan',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'root',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  'dz_',       // 数据表前缀
    'DB_CHARSET'            =>  'utf8',     // 网站编码
    


    'DB_PARAMS'             =>  array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL) ,
    'MODULE_ALLOW_LIST'     =>  array('Home', 'Admin', 'App'),//模块
    'DEFAULT_MODULE'        =>  'App',
    'DB_PARAMS'             =>  array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL) ,
    'MODULE_ALLOW_LIST'     =>  array('Home', 'Admin', 'App', 'GuangGao'),//模块

    'DB_PARAMS'             =>  array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),
    'MODULE_ALLOW_LIST'     =>  array('Home', 'Admin', 'App', 'GuangGao'),//模块

    'DEFAULT_MODULE'        =>  'App',

    'LOAD_EXT_CONFIG'       =>  'text' // 载入自定义配置文件text

    // 'URL_ROUTER_ON'   => true,
    // 'URL_ROUTE_RULES'=>array(
    //     'index$' => 'Home/Index/index', //定义路由
    // )

    // 'URL_ROUTER_ON'   => true,
    // 'URL_ROUTE_RULES'=>array(
    //     'index$' => 'Home/Index/index', //定义路由
    // )

);