<?php
// +----------------------------------------------------------------------
// | AccountService
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     AccountService.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Robots as robotModel;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\User\Component;

class LoginService extends BaseService
{

    //手机号密码登录方法
    public function mobile_login($data)
    {
        $common = new Common();
        $return = ['result'=>0,'msg'=>""];
        if(!isset($data['mobile'])){
            $return['msg'] = "手机号无效，请填写手机号码！";
        }else if(!$common->check_mobile($data['mobile'])){
            $return["msg"]="手机号格式错误，请填写正确的手机号码！";
        }else if(!isset($data['password'])){
            $return["msg"]="密码无效，请填写密码！";
        }else{
            $userinfo = UserInfo::findFirst(["username = '".$data['mobile']."'"]);
            if(!$userinfo || !$userinfo->userId){
                $return["msg"]="当前手机号尚未注册，请填写正确的手机号码！";
            }else if($userinfo->is_del==0){
                $return["msg"]="当前手机号已被禁用，禁止登录！";
            }else if($userinfo->password!=$data['password']){
                $return["msg"]="密码错误，请填写正确的密码！";
            }else{
                $return["result"]=1;
                $return["msg"]="登录成功！";
            }
        }
        return $return;
    }


	
}