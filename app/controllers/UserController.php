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
     * mobiel（必填）：账号
     * password（必填）：密码
     * */
	public function mobileLoginAction()
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
		$this->redis->set('user_token_'.$return['data']['user_info']['user_id'],$return['data']['user_token']);
		return $this->success($return['data']);
    }

	/*
     * 手机号验证码登录
     * 参数
     * mobile（必填）：账号
     * code（必填）：验证码
     * */
	public function mobileCodeLoginAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$code = isset($data['code'])?preg_replace('# #','',$data['code']):"";

		//echo $this->redis->get('login_'.$mobile);
		//$this->redis->set('login_'.$mobile,'{"code":123456}');
		//$this->redis->expire('login_'.$mobile,60);//设置过期时间,不设置过去时间时，默认为永久保持

		//调用手机号验证码登录方法
		$return  = (new UserService)->mobileCodeLogin($mobile,$code);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//用户token存入redis缓存中
		$this->redis->set('user_token_'.$return['data']['user_info']['user_id'],$return['data']['user_token']);
		$this->redis->expire('user_token_'.$return['data']['user_info']['user_id'],3600);//设置过期时间,不设置过去时间时，默认为永久保持
		return $this->success($return['data']);
	}

	/*
     * 忘记密码
     * 参数
     * mobile（必填）：账号
     * code（必填）：验证码
     * newpassword（必填）：新密码
     * */
	public function mobileForgetPwdAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$code = isset($data['code'])?preg_replace('# #','',$data['code']):"";
		$newpassword = isset($data['newpassword'])?preg_replace('# #','',$data['newpassword']):"";

		$this->redis->set('forget_'.$mobile,'{"code":123456}');
		$this->redis->expire('forget_'.$mobile,60);//设置过期时间,不设置过去时间时，默认为永久保持

		//调用忘记密码方法
		$return  = (new UserService)->mobileForgetPwd($mobile,$code,$newpassword);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success();
	}

	/*
     * 注册
     * 参数
     * mobile（必填）：账号
     * code（必填）：验证码
     * password（必填）：密码
     * company_user_id（必填）：企业用户名单主键ID
     * */
	public function mobileRegisterAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$code = isset($data['code'])?preg_replace('# #','',$data['code']):"";
		$password = isset($data['password'])?preg_replace('# #','',$data['password']):"";
		$company_user_id = isset($data['company_user_id'])?preg_replace('# #','',$data['company_user_id']):"";

		$this->redis->set('register_'.$mobile,'{"code":123456}');
		$this->redis->expire('register_'.$mobile,60);//设置过期时间,不设置过去时间时，默认为永久保持

		//调用注册方法
		$return  = (new UserService)->mobileRegister($mobile,$code,$password,$company_user_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//用户token存入redis缓存中
		$this->redis->set('user_token_'.$return['data']['user_info']['user_id'],$return['data']['user_token']);
		return $this->success($return['data']);
	}

	/*
     * 发送手机注册短信验证码
     * 参数
     * mobile（必填）：手机号
     * */
	public function sendRegisterCodeAction(){
		$code_name = 'register_';
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		//调用发送注册验证码方法
		$return  = (new SendCodeService)->sendRegisterCode($mobile,$code_name);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//短信验证码存入redis缓存中
		$this->redis->set($code_name.$mobile,$return['data']);
		$this->redis->expire($code_name.$mobile,60*5);
		return $this->success();
	}

	/*
     * 发送手机登录短信验证码
     * 参数
     * mobile（必填）：手机号
     * */
	public function sendLoginCodeAction(){
		$code_name = 'login_';
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		//调用发送登录验证码方法
		$return  = (new SendCodeService)->sendLoginCode($mobile,$code_name);

		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//短信验证码存入redis缓存中
		$this->redis->set($code_name.$mobile,$return['data']);
		$this->redis->expire($code_name.$mobile,60*5);
		return $this->success();
	}

	/*
     * 发送手机忘记密码短信验证码
     * 参数
     * mobile（必填）：手机号
     * */
	public function sendForgetCodeAction(){
		$code_name = 'forget_';
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		//调用发送忘记密码验证码方法
		$return  = (new SendCodeService)->sendForgetCode($mobile,$code_name);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//短信验证码存入redis缓存中
		$this->redis->set($code_name.$mobile,$return['data']);
		$this->redis->expire($code_name.$mobile,60*5);
		return $this->success();
	}

	/*
     * usertoken解密
     * 参数
     * user_token（必填）：用户token值
     * */
	public function getDecryptAction()
	{
		//接收参数并格式化
		$header = $this->request->getHeaders();
		print_r($header);
		$user_token = isset($header['Usertoken'])?preg_replace('# #','',$header['Usertoken']):"";
		//调用user_token解密方法
		$return  = (new UserService)->getDecrypt($user_token);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
     * 查询公司列表
     * 参数
     * company（必填）：公司名称
     * */
	public function getCompanyAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$company = isset($data['company'])?preg_replace('# #','',$data['company']):"";
		//调用公司查询方法
		$return  = (new UserService)->getCompany($company);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
     * 报名活动
     * 参数
     * mobiel（必填）：手机号
     * user_name（必填）：用户姓名
     * department（必填）：所属部门
     * activity_id（必填）：活动id
     * usertoken（必填）：用户token
     * */
	public function activitySignAction()
	{
		/*验证token开始*/
		$header = $this->request->getHeaders();
		$user_token = isset($header['Usertoken'])?preg_replace('# #','',$header['Usertoken']):"";
		$return  = (new UserService)->getDecrypt($user_token);
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		//接收参数并格式化
		$data = $this->request->get();
		$mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$user_name = isset($data['user_name'])?preg_replace('# #','',$data['user_name']):"";
		$department = isset($data['department'])?preg_replace('# #','',$data['department']):"";
		$activity_id = isset($data['activity_id'])?intval($data['activity_id']):0;
		//调用手机号密码登录方法
		$return  = (new UserService)->activitySign($mobile,$user_name,$department,$activity_id,$user_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);

	}

	/*
     * 完善用户信息
     * 参数
     * nick_name（选填）：用户昵称
     * true_name（选填）：用户姓名
     * sex（选填）：用户性别0保密1男2女  默认为零
     * usertoken（必填）：用户token
     * */
	public function fillUserinfoAction()
	{
		//验证token
		$header = $this->request->getHeaders();
		$user_token = isset($header['Usertoken'])?preg_replace('# #','',$header['Usertoken']):"";
		$return  = (new UserService)->getDecrypt($user_token);
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		$user_id = $return['data']['user_info']->user_id;
		//接收参数并格式化
		$data = $this->request->get();
		$nick_name = isset($data['nick_name'])?preg_replace('# #','',$data['nick_name']):"";
		$true_name = isset($data['true_name'])?preg_replace('# #','',$data['true_name']):"";
		$sex = isset($data['sex'])?preg_replace('# #','',$data['sex']):0;
		//调用完善用户方法
		$return  = (new UserService)->fillUserinfo($nick_name,$true_name,$sex,$user_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
    * 点赞
    * 参数
    * post_id（选填）：列表数据id
    * usertoken（必填）：用户token
    * */
	public function setKudosIncAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		/*验证token开始*/
		$header = $this->request->getHeaders();
		$user_token = isset($header['Usertoken'])?preg_replace('# #','',$header['Usertoken']):"";
		$return  = (new UserService)->getDecrypt($user_token);
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		$post_id = isset($data['post_id'])?preg_replace('# #','',$data['post_id']):0;
		//调用完善用户方法
		$return  = (new UserService)->setKudosInc($post_id,$user_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}



	public function ceshiWillieAction(){
		//接收参数并格式化
        $user_token = $this->request->getHeader('UserToken')?preg_replace('# #','',$this->request->getHeader('UserToken')):"";
        //调用user_token解密方法
        $return  = (new UserService)->getDecrypt($user_token);
        //返回值判断
        if($return['result']!=1){
			$request_url = $this->request->get('_url');
			print_r($request_url);
			return $this->failure(['jump_url'=>'/login'],$return['msg'],$return['code']);
		}
		$request = $this->request;
		var_dump($request->get());                          //默认获取所有的请求参数返回的是array效果和获取$_REQUEST相同
		var_dump($request->get('_url'));                     //获取摸个特定请求参数key的valuer和$_REQUEST['key']相同
		var_dump($request->getQuery('url', null, 'url'));   //获取get请求参数,第二个参数为过滤类型,第三个参数为默认值
		var_dump($request->getMethod());                    //获取请求的类型如果是post请求会返回"POST"
		var_dump($request->isAjax());                       //判断请求是否为Ajax请求
		var_dump($request->isPost());                       //判断是否是Post请求类似的有(isGet,isPut,isPatch,isHead,isDelete,isOptions等)
		var_dump($request->getHeaders());                   //获取所有的Header,返回结果为数组
		var_dump($request->getHeader('UserToken'));      //获取Header中的的莫一个指定key的指
		var_dump($request->getURI());                       //获取请求的URL比如phalcon.w-blog.cn/phalcon/Request获取的/phalcon/Request
		var_dump($request->getHttpHost());                  //获取请求服务器的host比如phalcon.w-blog.cn/phalcon/Request获取的phalcon.w-blog.cn
		var_dump($request->getServerAddress());             //获取当前服务器的IP地址
		var_dump($request->getRawBody());                   //获取Raw请求json字符
		var_dump($request->getJsonRawBody());               //获取Raw请求json字符并且转换成数组对象
		var_dump($request->getScheme());                    //获取请求是http请求还是https请求
		var_dump($request->getServer('REMOTE_ADDR'));       //等同于$_SERVER['REMOTE_ADDR']
		return $this->success();
	}


}
