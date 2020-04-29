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
use Elasticsearch\ClientBuilder;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;

class UserService extends BaseService
{
    private $msgList = [
        "mobile_empty"=>"手机号无效，请填写正确的手机号码！",
        "password_empty"=>"密码无效，请填写正确的密码！",
        "sendcode_empty"=>"验证码无效，请填写验证码！",
        "password_error"=>"密码错误，请填写正确的密码！",
        "sendcode_invalid"=>"验证码已失效，请重新发送！",
        "sendcode_error"=>"验证码错误，请填写正确的验证码！",
        "mobile_register"=>"手机号已注册，请填写正确的手机号码！",
        "mobile_noregister"=>"手机号尚未注册，请填写正确的手机号码！",
        "mobile_prohibit"=>"手机号已被禁用！",
        "login_success"=>"登录成功！",
        "changepwd_error"=>"密码修改失败！",
        "changepwd_success"=>"密码修改成功！",
        "register_success"=>"注册成功！",
        "register_error"=>"注册失败！",
        "decrypt_success"=>"解密成功！",
        "decrypt_error"=>"解密失败！",
        "code_status_error"=>"验证码状态修改失败！",
        "companyuser_status_error"=>"企业用户名单状态修改失败！",
    ];

    //手机号密码登录方法
    public function mobileLogin($mobile="",$password="")
    {
        $common = new Common();
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>404];
        if( empty($mobile) || !$common->check_mobile($mobile) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($password)){
            $return['msg']  = $this->msgList['password_empty'];
        }else{
            //查询用户数据
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','password','username','user_img']]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_noregister'];
            }else if($userinfo->is_del==0){
                $return['msg']  = $this->msgList['mobile_prohibit'];
            }else if($userinfo->password!=md5($password)){
                $return['msg']  = $this->msgList['password_error'];
            }else{
                //生成token值
                $oJwt = new ThirdJwt();
                $map = ['user_id' => $userinfo->user_id, 'username' => $userinfo->username, 'user_img' => $userinfo->user_img];
                $token = $oJwt::getToken($map);
                $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['user_info'=>$map,'user_token'=>$token]];
            }
        }
        return $return;
    }

    //手机号验证码登录方法
    public function mobileCodeLogin($mobile="",$code="")
    {
        $common = new Common();
        $login_code = $this->redis->get('login_'.$mobile);
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>404];
        if( empty($mobile) || !$common->check_mobile($mobile) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($code)){
            $return['msg']  = $this->msgList['sendcode_empty'];
        }else if(!$login_code){
            $return['msg']  = $this->msgList['sendcode_invalid'];
        }else if($code != json_decode($login_code)->code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }else{
            //查询用户数据
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','username','user_img']]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_noregister'];
            }else if($userinfo->is_del==0){
                $return['msg']  = $this->msgList['mobile_prohibit'];
            }else{
                //修改验证码记录状态
                $sendcode = SendCode::findFirst([
                    "to=:to: and type='mobile' and status=1 and code=:code:",
                    'bind'=>['to'=>$mobile, 'code'=>$code,],
                    'order'=>'id desc'
                ]);
                if($sendcode){
                    $sendcode->update(['status'=>0]);
                }
                //生成token值
                $oJwt = new ThirdJwt();
                $map = ['user_id' => $userinfo->user_id, 'username' => $userinfo->username, 'user_img' => $userinfo->user_img];
                $token = $oJwt::getToken($map);
                $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['user_info'=>$map, 'user_token'=>$token]];
            }
        }
        return $return;
    }

    //手机号忘记密码方法
    public function mobileForgetPwd($mobile="",$code="",$newpassword="")
    {
        $common = new Common();
        $forget_code = $this->redis->get('forget_'.$mobile);
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>404];
        if( empty($mobile) || !$common->check_mobile($mobile) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($code)){
            $return['msg']  = $this->msgList['sendcode_empty'];
        }else if(!$forget_code){
            $return['msg']  = $this->msgList['sendcode_invalid'];
        }else if($code != json_decode($forget_code)->code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }else if(empty($newpassword)){
            $return['msg']  = $this->msgList['password_empty'];
        }else{
            //查询用户数据
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'"]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_noregister'];
            }else if($userinfo->is_del==0){
                $return['msg']  = $this->msgList['mobile_prohibit'];
            }else{
                try {
                    //启用事务
                    $manager = new TxManager();
                    //指定你需要的数据库
                    $manager->setDbService("database_1");
                    // Request a transaction
                    $transaction = $manager->get();
                    //修改用户密码
                    $userinfo->setTransaction($transaction);
                    if($userinfo->update(['password'=>md5($newpassword)]) === false){
                        $transaction->rollback($this->msgList['changepwd_error']);
                    }
                    //查询并修改验证码状态
                    $sendcode = SendCode::findFirst([
                        "to=:to: and type='mobile' and status=1 and code=:code:",
                        'bind'=>['to'=>$mobile, 'code'=>$code,],
                        'order'=>'id desc'
                    ]);
                    if($sendcode){
                        $sendcode->setTransaction($transaction);
                        if($sendcode->update(['status'=>0]) === false){
                            $transaction->rollback($this->msgList['code_status_error']);
                        }
                    }
                    $return  = ['result'=>1, 'msg'=>$this->msgList['changepwd_success'], 'code'=>200];
                    $transaction->commit($return);
                } catch (TxFailed $e) {
                    // 捕获失败回滚的错误
                    $return['msg']  = $e->getMessage();
                }
            }
        }
        return $return;
    }

    //手机号注册方法
    public function mobileRegister($mobile="",$code="",$password="",$company_user_id="")
    {
        $common = new Common();
        $register_code = $this->redis->get('register_'.$mobile);
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>404];
        if( empty($mobile) || !$common->check_mobile($mobile) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($code)){
            $return['msg']  = $this->msgList['sendcode_empty'];
        }else if(!$register_code){
            $return['msg']  = $this->msgList['sendcode_invalid'];
        }else if($code != json_decode($register_code)->code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }else if(empty($password)){
            $return['msg']  = $this->msgList['password_empty'];
        }else{
            //查询用户数据
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','username','user_img']]);
            if(isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_register'];
                if($userinfo->is_del==0){
                    $return['msg']  = $this->msgList['mobile_prohibit'];
                }
            }else{
                try {
                    //启用事务
                    $manager = new TxManager();
                    //指定你需要的数据库
                    $manager->setDbService("database_1");
                    // Request a transaction
                    $transaction = $manager->get();
                    //添加用户
                    $user = new UserInfo();
                    $user->setTransaction($transaction);
                    $user->username = $mobile;
                    $user->password = md5($password);
                    if ($user->create() === false) {
                        $transaction->rollback($this->msgList['register_error']);
                    }
                    //修改验证码记录状态
                    $sendcode = SendCode::findFirst(["to=:to: and type='mobile' and status=1 and code=:code:", 'bind'=>['to'=>$mobile, 'code'=>$code,], 'order'=>'id desc']);
                    if($sendcode){
                        $sendcode->setTransaction($transaction);
                        if($sendcode->update(['status'=>0]) === false){
                            $transaction->rollback($this->msgList['code_status_error']);
                        }
                    }
                    //修改企业用户名单状态
                    $companyuserlist = CompanyUserList::findFirst(["id=:id:", 'bind'=>['id'=>$company_user_id], 'order'=>'id desc']);
                    if($companyuserlist){
                        $companyuserlist->setTransaction($transaction);
                        if($companyuserlist->update(['user_id'=>$user->user_id,'update_time'=>date('Y-m-d H:i:s',time())]) === false){
                            $transaction->rollback($this->msgList['companyuser_status_error']);
                        }
                    }
                    //生成token
                    $oJwt = new ThirdJwt();
                    $map = ['user_id' => $user->user_id, 'username' => $user->username??"", 'user_img' => $user->user_img??""];
                    $token = $oJwt::getToken($map);
                    $return  = ['result'=>1, 'msg'=>$this->msgList['register_success'], 'code'=>200, 'data'=>['user_info'=>$map, 'user_token'=>$token]];
                    $transaction->commit($return);
                } catch (TxFailed $e) {
                    // 捕获失败回滚的错误
                    $return['msg']  = $e->getMessage();
                }
            }
        }
        return $return;
    }

    //查询公司名称
    public function getCompany($company="")
    {
        //查询公司数据
        if(empty($company)){
            $usercompany = UserCompany::find(["is_del=1"]);
        }else{
            $usercompany = UserCompany::find([
                "company_name like :company_name: and is_del=1",
                'bind'=>['company_name'=>'%'.$company.'%'],
                "columns"=>['company_id','company_name']
            ]);
        }
        $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['usercompany'=>$usercompany]];
        return $return;
    }

    //用户token解密
    public function getDecrypt($user_token="")
    {
        $oJwt = new ThirdJwt();
        $user_info = $oJwt::getUserId($user_token);
        if($user_info){
            $user_info = json_decode($user_info);
            $return  = ['result'=>1, 'msg'=>$this->msgList['decrypt_success'], 'code'=>200, 'data'=>['user_info'=>$user_info]];
        }else{
            $return  = ['result'=>0, 'msg'=>$this->msgList['decrypt_error'], 'code'=>404, 'data'=>[]];
        }
        return $return;
    }














	
}