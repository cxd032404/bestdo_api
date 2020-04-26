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


class UserController extends BaseController
{

	/*
     * 手机号密码登录
     * 参数
     * username（必填）：账号
     * password（必填）：密码
     * */
	public function mobile_loginAction()
	{
		$data = $this->request->getQuery();
		$return  = (new LoginService)->mobile_login($data);
		$this->logger->info(json_encode($return));
		if($return['result']==1){
			return $this->success($return);
		}else{
			return $this->failure("",$return['msg']);
		}
    }

	/*
     * 手机号验证码登录
     * 参数
     * username（必填）：账号
     * password（必填）：密码
     * */
	public function mobile_code_loginAction()
	{
		$data = $this->request->getQuery();
		$return  = (new LoginService)->mobile_code_login($data);
		$this->logger->info(json_encode($return));
		if($return['result']!=1){
			return $this->failure("",$return['msg']);
		}
		return $this->success($return);
	}

	/*
     * 发送阿里云短信验证码
     * 参数
     * mobile（必填）：手机号
     * */
	public function sendcode(){
		$mobile = 17082170787;
		$return  = (new SendCodeService)->sendRegCode($mobile);
		if($return['code'] != 1){
			return $this->failure($return['msg']);
		}
		return $this->success($return['msg']);
	}






}
