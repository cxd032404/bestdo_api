<?php
// +----------------------------------------------------------------------
// | 配置文件 静态资产文件加载
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     config.php
// |
// | Author:   huzhichao502@gmail.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
$assets = array();
// 全局样式
$assets["global"] = [
    "name"      =>"global",             // 名称，也可以做集合名称来使用
    "title"     =>"全局",                // 标题
    "version"   =>"1.0.0",              // 版本 
    "prefix"    =>"",                   // 前缀,移服务器方便
    "target"    =>"global",             // 如果压缩后，产生的文件新名称
    "css" => [
    
    ],
    "js" => [	
    	["path" => "public/js/jquery-3.2.1.min.js"],
	]
];
// 公共样式
$assets["common"] = array();
 
return $assets;