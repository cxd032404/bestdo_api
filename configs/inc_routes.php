<?php
// +----------------------------------------------------------------------
// | 配置文件 路由地址配置
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     inc_config.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
$router = new Phalcon\Mvc\Router();


// 默认路由器
$router -> setDefaults([
    'controller' => 'index',
    'action' => 'index'
]);

// NOT FOUND 路由器
$router -> notFound([
    "controller" => 'index',
    "action" => 'notFound'
]);

$router -> add('api/test/', [
    'controller' => 'api',
    'action' => 'test'
]);
//获取页面数据
$router -> add('page/getPage/{company}/{page_sign}', [
    'controller' => 'page',
    'action' => 'getPage'
]);
//搜索导入的名单
$router -> add('search/companyUser/{company_id}/{query}/{page}/{page_size}', [
    'controller' => 'search',
    'action' => 'companyUser'
]);
//提交文章
$router -> add('list/post/', [
    'controller' => 'list',
    'action' => 'post'
]);



return $router;
