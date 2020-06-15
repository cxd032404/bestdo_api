<?php
// +----------------------------------------------------------------------
// | 配置文件 参数配置
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     inc_config.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Logger;

$config = [
    'application' => [
        'path_views'        => ROOT_PATH . '/app/views/',
        'path_volt'         => ROOT_PATH . '/runtime/volt/',
    	'path_public'       => ROOT_PATH . '/public/',
        'path_cache'        => ROOT_PATH . '/runtime/cache/',
        'debug'             => '0',
        'lifetime'          => 0,
        'cache_prefix'      => 'phalcon',
        'js_css_version'    => '',
        'keywords'          => '',
        'description'       => '',
        'suffix'            => '队列消耗',
        'description'       => '',
    ],
    'hj_config' => [ // 配置库
        'adapter'  => 'Mysql',
        'host'     => '192.168.31.155',
        'username' => 'mbtest',
        'password' => 'Bestdo123',
        'dbname'   => 'hj_config'
    ],
    'hj_user' => [ // 配置库
        'adapter'  => 'Mysql',
        'host'     => '192.168.31.155',
        'username' => 'mbtest',
        'password' => 'Bestdo123',
        'dbname'   => 'hj_user'
    ],
    'database' => [ // 测试库1
        'adapter'  => 'Mysql',
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'dbname'   => 'swoole_test'
    ],
    'database_1' => [ // 测试库2
        'adapter'  => 'Mysql',
        'host'     => '127.0.0.1',
        'username' => 'root',
        'password' => 'root',
        'dbname'   => 'swoole_test2'
    ],
    'redis' => [
        'adapter'       => 'Redis',
        'host'          => '192.168.31.155:6379',
        //'port'   		=> 6379,
        'auth_password' => '123456',
        "persistent"    => false,
        'lifttime'      => 86400
    ],
    'elasticsearch' => [
        'adapter'       => 'Elasticsearch',
        //'host'          => ['127.0.0.1:9200'],//['192.168.31.155:9200'],
        'host'          => ['192.168.31.155:9200'],

        "persistent"    => false,
        'lifttime'      => 86400
    ],
    'autoload'=>[
       'path_tasks'     => ROOT_PATH . '/app/tasks/',
       'path_librarys'  => ROOT_PATH . '/app/librarys/',
       'path_services'  => ROOT_PATH . '/app/services/'
    ],
    'logger' => [
        'adapter' =>'logger',
        'path'     => ROOT_PATH . '/runtime/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'H:i:s',
        'logLevel' => Phalcon\Logger::DEBUG,
        'filename' => 'logger-'.date('Y-m-d') .'.log',
    ],
    'request_logger' => [
        'adapter' =>'logger',
        'path'     => ROOT_PATH . '/runtime/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'H:i:s',
        'logLevel' => Phalcon\Logger::DEBUG,
        'filename' => 'request-'.date('Y-m-d') .'.log',
    ],
    'response_logger' => [
        'adapter' =>'logger',
        'path'     => ROOT_PATH . '/runtime/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'H:i:s',
        'logLevel' => Phalcon\Logger::DEBUG,
        'filename' => 'response-'.date('Y-m-d') .'.log',
    ],
    'upload' => [
        'prefix'        => 'language',
        'mimes'         =>  ['xlsx','xls','doc'], //允许上传的文件MiMe类型
        'maxSize'       =>  1048576, //上传的文件大小限制 (0-不做限制 1048576:1M大小)
        'exts'          =>  ['xlsx','xls','doc'], //允许上传的文件后缀
        'rootPath'      =>  '/public/uploads/' //保存根路径
    ],
    'headers' => [
        'Developer' 	=> 'phalcon',
        'X_Powered_By' 	=> 'Multi Concise Framework 3.4',
        'Server' 		=> 'Leopard Server 10',
        'Content_Type' 	=> 'application/json;charset=utf-8',
        'Status_Code' 	=> 'okey!'
    ],
    'cache_settings' =>[
        'list'=>['name'=>'list_','expire'=>3600],//列表
        'post'=>['name'=>'user_posts_','expire'=>3600],//文章
        'wechat'=>['name'=>'wechat_token_','expire'=>3600],//微信token
        'accessToken'=>['name'=>'wechat_access_token','expire'=>7200],//微信access_token
        'user_token'=>['name'=>'user_token_','expire'=>3600*24*30],//用户登录token
        'user_club_permission'=>['name'=>'user_club_permission_','expire'=>3600],//用户对特定俱乐部的权限
        'user_info'=>['name'=>'user_info_','expire'=>3600],//用户信息
        'user_club_membership'=>['name'=>'user_club_membership_','expire'=>3600],//用户对特定俱乐部的身份
        'user_club_list'=>['name'=>'user_club_list_','expire'=>3600],//用户拥有权限的俱乐部列表
        'club_info'=>['name'=>'club_info_','expire'=>3600],//俱乐部信息
        'club_list_by_company'=>['name'=>'club_list_by_company_','expire'=>3600],//单个企业下的俱乐部列表
    ]



];
return $config;