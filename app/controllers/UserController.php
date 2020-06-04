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
		$this->redis->expire('user_token_'.$return['data']['user_info']['user_id'],$this->config->user_token->exceed_time);//设置过期时间,不设置过去时间时，默认为永久保持
		return $this->success($return['data']);
    }

	/*
     * 手机号验证码登录
     * 参数
     * mobile（必填）：账号
     * logincode（必填）：验证码
	 * companyuser_id （必填）企业导入名单id
     * */
	public function mobileCodeLoginAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
        $this->logger->info(json_encode($data));
        $mobile = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$logincode = isset($data['logincode'])?preg_replace('# #','',$data['logincode']):"";
		$companyuser_id = isset($data['companyuser_id'])?preg_replace('# #','',$data['companyuser_id']):0;
		$code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
		//调用手机号验证码登录方法
		$return  = (new UserService)->mobileCodeLogin($mobile,$logincode,$companyuser_id,$code);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		//用户token存入redis缓存中
		$this->redis->set('user_token_'.$return['data']['user_info']['user_id'],$return['data']['user_token']);
		$this->redis->expire('user_token_'.$return['data']['user_info']['user_id'],$this->config->user_token->exceed_time);//设置过期时间,不设置过去时间时，默认为永久保持
		return $this->success($return['data']);
	}
    /*
     * 微信code登录
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function wechatCodeLoginAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $this->logger->info(json_encode($data));
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        //调用手机号验证码登录方法
        $openId = (new WechatService)->getOpenIdByCode($this->key_config->aliyun->wechat,$code);
        //日志记录
        $this->logger->info(json_encode($openId));
        return $this->success($openId);
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
		$this->redis->expire('user_token_'.$return['data']['user_info']['user_id'],$this->config->user_token->exceed_time);//设置过期时间,不设置过去时间时，默认为永久保持
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
     * UserToken（必填）：用户token值
     * */
	public function getDecryptAction()
	{
		//调用user_token解密方法
		$return  = (new UserService)->getDecrypt();
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
     * company_id（必填）：公司id
     * */
	public function getCompanyAction()
	{
		//接收参数并格式化
		$companyService = new CompanyService();
        $data = $this->request->get();
		$company_id = isset($data['company_id'])?preg_replace('# #','',$data['company_id']):0;
        $privacy = isset($data['privacy'])?preg_replace('# #','',$data['privacy']):0;
        $user = isset($data['user'])?preg_replace('# #','',$data['user']):0;
		//调用公司查询方法
        $company_info  = $companyService->getCompanyInfo($company_id);
        if($company_info)
        {
            $company_info = $company_info->toArray();
            if($privacy)
            {
                $protocal = $companyService->getCompanyProtocal($company_id,"privacy");
                if($protocal)
                {
                    $company_info['protocal']['privacy'] = $protocal?$protocal->toArray():[];
                }
            }
            if($user)
            {
                $protocal = $companyService->getCompanyProtocal($company_id,"user");
                if($protocal)
                {
                    $company_info['protocal']['user'] = $protocal?$protocal->toArray():[];
                }
            }
            return $this->success($company_info);
        }
        else
        {
            return $this->failure([],"",404);
        }
	}
    /*
 * 查询公司列表
 * 参数
 * */
    public function getCompanyListAction()
    {
        //接收参数并格式化
        $companyService = new CompanyService();
        $companyList = $companyService->getCompanyList();
        return $this->success($companyList);
    }

	/*
     * 报名活动
     * 参数
     * mobiel（必填）：手机号
     * user_name（必填）：用户姓名
     * department（必填）：所属部门
     * activity_id（必填）：活动id
     * UserToken（必填）：用户token
     * */
	public function activitySignAction()
	{
		/*验证token开始*/
		$return  = (new UserService)->getDecrypt();
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		//接收参数并格式化
		$data = $this->request->get();
		$map['mobile'] = isset($data['mobile'])?substr(preg_replace('# #','',$data['mobile']),0,11):"";
		$map['user_name'] = isset($data['user_name'])?preg_replace('# #','',$data['user_name']):"";
		$map['department'] = isset($data['department'])?preg_replace('# #','',$data['department']):"";
		$map['activity_id'] = isset($data['activity_id'])?intval($data['activity_id']):0;
		//调用手机号密码登录方法
		$return  = (new UserService)->activitySign($map,$user_id);
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
     * UserToken（必填）：用户token
     * */
	public function updateUserInfoAction()
	{
		//验证token
		$return  = (new UserService)->getDecrypt();
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		$user_id = $return['data']['user_info']->user_id;
		//接收参数并格式化
		$data = $this->request->get();
		$map['nick_name'] = isset($data['nick_name'])?preg_replace('# #','',$data['nick_name']):"";
		$map['true_name'] = isset($data['true_name'])?preg_replace('# #','',$data['true_name']):"";
		$map['sex'] = isset($data['sex'])?preg_replace('# #','',$data['sex']):0;
		//调用完善用户方法
		$return  = (new UserService)->updateUserInfo($map,$user_id);
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
    * post_id（必填）：列表内容id
    * UserToken（必填）：用户token
    * */
	public function setKudosIncAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		/*验证token开始*/
		$return  = (new UserService)->getDecrypt();
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$sender_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		$post_id = isset($data['post_id'])?preg_replace('# #','',$data['post_id']):0;
		//调用完善用户方法
		$return  = (new UserService)->setKudosInc($post_id,$sender_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data'],$return['msg']);
	}

	/*
    * 取消点赞
    * 参数
    * post_id（必填）：列表内容id
    * UserToken（必填）：用户token
    * */
	public function setKudosDecAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		/*验证token开始*/
		$return  = (new UserService)->getDecrypt();
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$sender_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		$post_id = isset($data['post_id'])?preg_replace('# #','',$data['post_id']):0;
		//调用完善用户方法
		$return  = (new UserService)->setKudosDec($post_id,$sender_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
    * 后台获取用户token
    * 参数
    * manager_id（必填）：后台用户id
    * */
	public function createTokenForManagerAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$manager_id = isset($data['manager_id'])?preg_replace('# #','',$data['manager_id']):0;
		//调用完善用户方法
		$return  = (new UserService)->createTokenForManager($manager_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
     * 验证用户数据
     * 参数
     * company_id（必填）：企业id
     * worker_id（必填）：工号
     * name（必填）：姓名
     * */
	public function checkoutCompanyAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		$company_id = isset($data['company_id'])?preg_replace('# #','',$data['company_id']):0;
		$worker_id = isset($data['worker_id'])?preg_replace('# #','',$data['worker_id']):"";
		$name = isset($data['name'])?preg_replace('# #','',$data['name']):"";

		//调用手机号验证码登录方法
		$return  = (new UserService)->checkoutCompany($company_id,$worker_id,$name);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data']);
	}

	/*
	* 隐藏活动记录
	* 参数
	* post_id（必填）：列表内容id
	* UserToken（必填）：用户token
	* */
	public function setActivityPostsAction()
	{
		//接收参数并格式化
		$data = $this->request->get();
		/*验证token开始*/
		$return  = (new UserService)->getDecrypt();
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		/*验证token结束*/
		$user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
		$post_id = isset($data['post_id'])?preg_replace('# #','',$data['post_id']):0;
		//调用完善用户方法
		$return  = (new UserService)->setActivityPosts($post_id,$user_id);
		//日志记录
		$this->logger->info(json_encode($return));
		//返回值判断
		if($return['result']!=1){
			return $this->failure([],$return['msg'],$return['code']);
		}
		return $this->success($return['data'],$return['msg']);
	}







}
