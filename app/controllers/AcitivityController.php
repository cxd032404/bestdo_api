<?php
// +----------------------------------------------------------------------
// | API控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     api.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Phalcon\Mvc\Controller;


class ActivityController extends BaseController
{
	
	/*
     * 创建活动
     * 参数
     * activityName（必填）：活动名称
	 * club_id（必填）：对应俱乐部
     * password（必填）：密码
     * */
	public function acitivyCreateAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$password = isset($data['password'])?preg_replace('# #','',$data['password']):"";
		//调用手机号密码登录方法
		$return  = (new UserService)->mobileLogin($mobile,$password);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//用户token存入redis缓存中
        $cacheSetting = $this->config->cache_settings->user_token;
		$cacheName = $cacheSetting->name.$return['data']['user_info']['user_id'];
		$this->redis->set($cacheName,$return['data']['user_token']);
		$this->redis->expire($cacheName,$cacheSetting->expire);//设置过期时间,不设置过去时间时，默认为永久保持
		return $this->success($return['data']);
    }
}
