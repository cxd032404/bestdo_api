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
        'path_services'  => ROOT_PATH . '/app/services/',
        'path_model'     => ROOT_PATH . '/app/models/'
    ],
    'wechat_decrypt_logger' => [
        'adapter' =>'logger',
        'path'     => ROOT_PATH . '/runtime/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'H:i:s',
        'logLevel' => Phalcon\Logger::DEBUG,
        'filename' => 'wechat_decrypt-'.date('Y-m-d') .'.log',
    ],
    'wechat_code_logger' => [
        'adapter' =>'logger',
        'path'     => ROOT_PATH . '/runtime/logs/',
        'format'   => '%date% [%type%] %message%',
        'date'     => 'H:i:s',
        'logLevel' => Phalcon\Logger::DEBUG,
        'filename' => 'wechat_code-'.date('Y-m-d') .'.log',
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
    'checkin_max_distance' => 1000,
    'cache_settings' =>[
        'company_info'=>['name'=>'company_info_','expire'=>3600*24],//企业信息
        'page_info_id'=>['name'=>'page_info_id_','expire'=>3600],//根据页面ID获取页面
        'page_info_sign'=>['name'=>'page_info_sign_','expire'=>3600],//根据页面标示获取页面
        'page_element_list'=>['name'=>'page_element_list_','expire'=>3600],//页面元素列表
        'list'=>['name'=>'list_','expire'=>3600],//列表
        'post'=>['name'=>'user_posts_','expire'=>3600],//文章
        'wechat'=>['name'=>'wechat_token_','expire'=>3600],//微信token
        'accessToken'=>['name'=>'wechat_access_token','expire'=>7200],//微信access_token
        'wechat_code'=>['name'=>'wechat_code_','expire'=>3600*24*30],//微信code缓存
        'wechat_openid'=>['name'=>'wechat_openid_','expire'=>3600*24*30],//微信openid用户信息缓存
        'mini_program_openid'=>['name'=>'mini_program_openid_','expire'=>3600*24*30],//小程序openid用户信息缓存
        'mini_program_code'=>['name'=>'mini_program_code_','expire'=>3600*24*30],//小程序code缓存
        'user_club_permission'=>['name'=>'user_club_permission_','expire'=>3600],//用户对特定俱乐部的权限
        'user_info'=>['name'=>'user_info_','expire'=>3600],//用户信息
        'user_club_membership'=>['name'=>'user_club_membership_','expire'=>3600],//用户对特定俱乐部的身份
        'user_club_list'=>['name'=>'user_club_list_','expire'=>3600],//用户拥有权限的俱乐部列表
        'club_info'=>['name'=>'club_info_','expire'=>3600],//俱乐部信息
        'club_list_by_company'=>['name'=>'club_list_by_company_','expire'=>3600],//单个企业下的俱乐部列表
        'activity_list_by_company'=>['name'=>'activity_list_by_company_','expire'=>3600],//单个企业下的活动列表
        'activity_list_by_creater'=>['name'=>'activity_list_by_creater_','expire'=>3600],//单个企业下特定用户创建的活动列表
        'activity_info'=>['name'=>'activity_info_','expire'=>3600],//活动
        'department_info'=>['name'=>'department_info_','expire'=>3600],//部门信息
        'department_parent'=>['name'=>'department_parent_','expire'=>3600],//父级子部门信息
        'department_user_count'=>['name'=>'department_user_count_','expire'=>3600],//部门（包含子部门）人数
        'steps_data'=>['name'=>'steps_rank_','expire'=>600],
        'config'=>['name'=>'config_','expire'=>86400*7],
        'activity_member_count'=>['name'=>'activity_member_count_','expire'=>3600],
        'wechat_comment_check'=>['name'=>'wechat_comment_check_','expire'=>1200],
    ],
    'special_code'=>[
        "need_club_membership"=>999, //需要加入俱乐部
        "activity_member_full"=>998, //活动成员已满
    ],
    'steps'=>[
        'stepsPerKcal' => 20,
        'distancePerStep' => 0.6,
        'stepsPerMinute' => 30,
        'defaultDailyStep' => 6000,
    ],
    'activity'=>[
        'activity_checkin_time'=>3600,//距活动开始前有效签到时间
        ],
    'testMoblie'=>['18550306937','18365285403','17621822661','13472871514','17082170787','18621758237','15150731270','15150731271','15150731272','15150731273']
];
return $config;