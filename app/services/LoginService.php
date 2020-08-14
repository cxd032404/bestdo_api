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
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\User\Component;
use Elasticsearch\ClientBuilder;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;

class LoginService extends BaseService
{

    private $kudosTypeList = ['kudo'=>"点赞",'vote'=>"投票"];
    private $msgList = [
        "mobile_empty"=>"手机号无效，请填写正确的手机号码！",
        "companyuser_empty"=>"账户验证失败，当前账户尚未获得登录/注册权限！",
        "posts_empty"=>"列表内容查询不到，请选择正确的列表内容！",
        "company_id_empty"=>"企业编号无效",
        "worker_id_empty"=>"请输入工号",
        "name_empty"=>"请输入姓名",
        "company_user_existed"=>"此手机号码已绑定其他用户，请更换其他手机号码！",

        "password_error"=>"密码错误，请填写正确的密码！",
        "sendcode_error"=>"验证码错误，请填写正确的验证码！",
        "changepwd_error"=>"密码修改失败！",
        "login_fail"=>"登录失败！",
        "register_fail"=>"注册失败！",
        "decrypt_fail"=>"用户token解析失败！",
        "code_status_error"=>"验证码状态修改失败！",
        "update_userinfo_fail"=>"用户信息更新失败！",
        "kudos_fail"=>"点赞失败！",
        "companyuser_update_fail"=>"企业用户名单状态修改失败！",
        "posts_kudos_fail"=>"点赞记录新增失败！",
        "posts_remove_fail"=>"取消点赞失败！",
        "posts_delete_fail"=>"活动记录删除失败！",
        "posts_kudos_update_fail"=>"点赞记录修改失败！",
        "manager_id_error"=>"后台用户id无效！",
        "manager_id_invalid"=>"后台用户id无效,无对应用户信息！",
        "activity_list_error"=>"您已提交过本次活动作品，不可重复提交！",
        "company_user_error"=>"企业用户身份验证失败！",

        "sendcode_invalid"=>"验证码已失效，请重新发送！",
        "user_openid_valid"=>"用户openid无法匹配用户",
        "user_unionid_valid"=>"用户unionid无法匹配用户",

        "user_token_invalid"=>"用户token已失效，请登录！",

        "mobile_register"=>"手机号已注册，请填写正确的手机号码！",
        "mobile_noregister"=>"手机号尚未注册，请填写正确的手机号码！",

        "login_success"=>"登录成功！",
        "changepwd_success"=>"密码修改成功！",
        "register_success"=>"注册成功！",
        "decrypt_success"=>"用户token解析成功！",
        "update_userinfo_success"=>"用户信息更新成功！",
        "posts_success"=>"成功！",
        "posts_del_success"=>"活动记录删除成功！",
        "posts_remove_success"=>"取消点赞成功！",
        "token_for_manager_success"=>"获取token成功！",
        "company_user_success"=>"企业用户身份验证成功！",

        "mobile_prohibit"=>"手机号已被禁用！",
        "wechat_prohibit"=>"微信号已被禁用！",
        "activity_signin"=>"您已报名本次活动，无法重复报名，请选择正确的活动！",
        "activity_expire"=>"当前时间不在报名时间内！",
        "posts_kudo_exist"=>"您今天已点赞过此内容，不可重复点赞！",
        "posts_vote_exist"=>"您已经投过票了,明天再来吧!（投票后不可取消~）",
        "posts_kudos_noexist"=>"您今天尚未点赞过此内容，不可取消点赞！",

        "activity_not_no"=>"活动尚未开启，请耐心等待！",
        "activity_ended"=>"活动已结束，不可报名！",
        "openid_used"=>"您所使用微信账号已经绑定了其他的手机号码",
        "wechat_used"=>"您所使用微信账号已经绑定了其他的手机号码",
        "wechat_mobile_used"=>"您所使用手机号码已经绑定了其他的微信账号",
        "miniprogram_used"=>"您所使用小程序账号已经绑定了其他的手机号码",
        "miniprogram_mobile_used"=>"您所使用手机号码已经绑定了其他的小程序账号",
    ];

    public function mobileCodeLogin($mobile="",$logincode="",$companyuser_id=0,$code="",$miniProgramUserInfo = "",$company_id = 0,$company_name = "",$app_id = 101)
    {
        $oCompanyService = new CompanyService();
        $oUserService = new UserService();
        //基础校验
        //手机号码/验证码为空校验
        //验证码码有效性校验
        $checkMobile = $this->checkMobileCode($mobile,$logincode);
        if($checkMobile['result']==false)
        {
            return $checkMobile;
        }
        //获取可能可用的手机号码对应用户用以登录
        $userToLogin = $this->getUserToLogin($mobile,$code,$miniProgramUserInfo,$app_id);
        if($userToLogin['result']==true)
        {
            //用作登录的用户数据
            $userInfo = $userToLogin['mobileUser'];
        }
        else
        {
            return ['rusult'=>"false","msg"=>$this->msgList[$userToLogin['msg']],"code"=>400];
        }
        if(isset($userInfo->user_id))
        {
            //用户找到，不需要对应的名单ID了
            $companyuser_id = 0;
            //登录流程
            $login = $this->loginByUser($userInfo->user_id,$app_id);
            //修改验证码记录状态
            $sendcode = $oUserService->setMobileCode($mobile,$logincode);
            return $login;
        }
        else//没找到用户
        {
            if($companyuser_id == 0)
            {
                if($company_id == 0)
                {
                    if($company_name != "")
                    {
                        //创建企业
                        $createCompany = $oCompanyService->createCompany(["company_name"=>$company_name,"member_limit"=>10,'parent_id'=>0,'display'=>1]);
                        if($createCompany['result']==true)
                        {
                            $createUser = $oUserService->createUser(["username"=>$mobile,'nickname'=>"用户".$mobile,'true_name'=>"用户".$mobile,
                                "company_id"=>$createCompany['companyInfo']->company_id,
                                'mobile'=>$mobile,'department_id'=>0,
                                'department_id_1'=>0,'department_id_2'=>0,'department_id_3'=>0,'last_login_source'=>"Mobile",'is_del'=>0]);
                            if($createUser['result']==true)
                            {
                                //登录流程
                                $login = $this->loginByUser($createUser['userInfo']->user_id,$app_id);
                                $oCompanyService->updateCompanyInfo($createCompany['companyInfo']->company_id,['create_user_id'=>$createUser['userInfo']->user_id]);
                                return $login;
                            }
                            else
                            {
                                return ['rusult'=>"false","msg"=>$this->msgList["login_fail"],"code"=>400];
                            }
                        }
                        else
                        {
                            return ['rusult'=>"false","msg"=>$this->msgList["login_fail"],"code"=>400];
                        }
                    }
                    else
                    {
                        return ['rusult'=>"false","msg"=>$this->msgList["login_fail"],"code"=>400];
                    }
                }
                else
                {
                    $createUser = $oUserService->createUser(["username"=>$mobile,'nickname'=>"用户".$mobile,'true_name'=>"用户".$mobile,
                        "company_id"=>$company_id,
                        'mobile'=>$mobile,'department_id'=>0,
                        'department_id_1'=>0,'department_id_2'=>0,'department_id_3'=>0,'last_login_source'=>"Mobile",'is_del'=>0]);
                    if($createUser['result']==true)
                    {
                        //登录流程
                        $login = $this->loginByUser($createUser['userInfo'],$app_id);
                        return $login;
                    }
                    else
                    {
                        return ['rusult'=>"false","msg"=>$this->msgList["login_fail"],"code"=>400];
                    }
                }
            }
            else
            {
                //查询企业导入名单
                $companyuserInfo = \HJ\CompanyUserList::findFirst([
                    "id=:companyuser_id:",
                    'bind'=>[
                        'companyuser_id'=>$companyuser_id,
                    ],
                    'order'=>'id desc'
                ]);
                //如果没查到
                if(!isset($companyuserInfo->id))
                {
                    //返回失败
                    return ['rusult'=>"false","msg"=>$this->msgList["companyuser_empty"],"code"=>400];
                }
                else
                {
                    //获取对应的部门信息
                    $department = (new DepartmentService())->getDepartment($companyuserInfo->department_id);
                    //创建用户
                    $createUser = $oUserService->createUser(["username"=>$mobile,
                        "company_id"=>$companyuserInfo->company_id,
                        'mobile'=>$mobile,'department_id'=>department_id,
                        'department_id_1'=>$department['department_id_1'],'department_id_2'=>$department['department_id_2'],'department_id_3'=>$department['department_id_3'],
                        'worker_id'=>$companyuserInfo->worker_id,"true_name"=>$companyuserInfo->name,"nick_name"=>$companyuserInfo->name,
                        'last_login_source'=>"Mobile"]);
                    if($createUser['result']==true)
                    {
                        //登录流程
                        $login = $this->loginByUser($createUser['userInfo']->user_id,$app_id);
                        return $login;
                    }
                    else
                    {
                        return ['rusult'=>"false","msg"=>$this->msgList["login_fail"],"code"=>400];
                    }
                }
            }
        }
    }
    //微信通过openID登录
    public function miniProgramLogin($unionId = "",$miniprogramId = "",$app_id)
    {
        $oUserService = (new UserService());
        //通过Openid查找用户
        $userinfo = $oUserService->getUserInfoByUnionId($unionId);
        //如果没找到
        if(!$userinfo)
        {
            //通过openid查找用户
            $userinfo = $oUserService->getWechatUserInfoByOpenId($miniprogramId,$app_id);
            if(!isset($userinfo[$app_id]))
            {
                $return = [];
                $return['result'] = 0;
                $return['msg']  = $this->msgList['user_unionid_valid'];
                $return['code']  = 403;
            }
            else
            {
                $userinfo[$app_id] = $oUserService->getUserInfo($userinfo[$app_id]['user_id']);
                if($userinfo[$app_id]->is_del==1)
                {
                    $return = [];
                    $return['result'] = 0;
                    $return['msg']  = $this->msgList['wechat_prohibit'];
                }
                else
                {
                    $userinfo = $userinfo[$app_id];
                }
            }
        }
        if(!isset($return['result']))
        {
            //登录流程
            $login = $this->loginByUser($userinfo['user_id'],$app_id);
            return $login;
        }
        return $return;
    }
    //微信通过openID登录
    public function wechatLogin($openId = "",$app_id=101)
    {
        $oUserService = (new UserService());
        //通过openid查找用户
        $userinfo = $oUserService->getWechatUserInfoByOpenId($openId,$app_id);
        if(!isset($userinfo[$app_id]))
        {
            $return = [];
            $return['result'] = 0;
            $return['msg']  = $this->msgList['user_unionid_valid'];
            $return['code']  = 403;
        }
        else
        {
            if($userinfo[$app_id]->is_del==1)
            {
                $return = [];
                    $return['result'] = 0;
                    $return['msg']  = $this->msgList['wechat_prohibit'];
            }
            else
            {
                $userinfo = $userinfo[$app_id];
            }
        }
        if(!isset($return['result']))
        {
            //登录流程
            $login = $this->loginByUser($userinfo['user_id'],$app_id);
            return $login;
        }
        return $return;
    }
    //校验手机号码和验证码
     public function checkMobileCode($mobile,$logincode)
     {
         $login_code = $this->redis->get('login_'.$mobile);
         //后台配置的测试号码
         $test_phone_number =  (new ConfigService())->getConfig("phoneNumber")->content??'';
         //配置文件中的测试号
         $testMoblie = $this->config->testMoblie;
         if(strstr($test_phone_number,$mobile) || in_array($mobile,(array)$testMoblie)){
             $login_code = json_encode(['code'=>123456]);
         }
         $return = ['result'=> true,'data'=>[],'msg'=>"",'code'=>200];
         if( empty($mobile) || !(new Common())->check_mobile($mobile) )
         {
             $return['result'] = false;
             //手机号码无效
             $return['msg']  = $this->msgList['mobile_empty'];
         }
         elseif(empty($logincode) || ($logincode != json_decode($login_code)->code))
         {
             $return['result'] = false;
             //验证码为空或者错误
             $return['msg']  = $this->msgList['sendcode_error'];
         }
         return $return;
     }
    //获取可能可用的手机号码对应用户用以登录
    public function getUserToLogin($mobile,$code,$miniProgramUserInfo,$app_id)
     {
         $oWechatService = new WechatService();
         $oUserService = new UserService();
         $available = ['result'=>true,"mobileUser" => []];
         if(!empty($code))
         {
             //通过code获取到微信的用户信息
             $WechatUserInfo = $oWechatService->getUserInfoByCode_Wechat($code,$app_id);
             if(isset($WechatUserInfo['openid']))
             {
                 //检查手机号和微信Openid是否配对组合可用
                 $available = $this->checkMobileAvailable($WechatUserInfo['openid'],$mobile,$app_id);
                 if($available['result']==0)
                 {
                    //$return = ['result'=>0,'data'=>[],'msg'=>$this->msgList[$available['msg']],'code'=>400];
                 }
                 else
                 {
                     if($available['self']==1)
                     {
                         $oWechatService->updateUserWithWechat($available['mobileUser']->user_id,$code,$app_id);
                         $oUserService->updateUserOpenId($available['mobileUser']->user_id,$WechatUserInfo['openid'],$app_id);
                     }
                 }
             }
             else
             {
                 $mobileUser = $oUserService->getUserInfoByMobile($mobile);
                 if(isset($mobileUser->user_id))
                 {
                     $available['mobileUser'] =  $mobileUser;
                 }
                 else
                 {
                     $available['mobileUser'] = [];
                 }
             }
         }
         elseif(!empty($miniProgramUserInfo))
         {
             $code = json_decode($miniProgramUserInfo,true)['code'];
             $miniProgramUser = $oWechatService->getUserInfoByCode_mini_program($this->key_config->tencent,$code,$app_id);
             if(isset($miniProgramUser['openid']))
             {
                 $available = $this->checkMobileAvailable($miniProgramUser['openid'],$mobile,$app_id);
                 if($available['result']==0)
                 {
                     //$return = ['result'=>0,'data'=>[],'msg'=>$this->msgList[$available['msg']],'code'=>400];
                 }
                 else
                 {
                     if($available['self']==1)
                     {
                         $oWechatService->updateUserWithMiniProgram($available['mobileUser']->user_id,$miniProgramUserInfo,$app_id);
                         $oUserService->updateUserOpenId($available['mobileUser']->user_id,$miniProgramUser['openid'],$app_id);
                     }
                 }
             }
             else
             {
                 $mobileUser = $oUserService->getUserInfoByMobile($mobile);
                 if(isset($mobileUser->user_id))
                 {
                     $available['mobileUser'] =  $mobileUser;
                 }
                 else
                 {
                     $available['mobileUser'] = [];
                 }
             }
         }
         else
         {
             $mobileUser = $oUserService->getUserInfoByMobile($mobile);
             if(isset($mobileUser->user_id))
             {
                 $available['mobileUser'] =  $mobileUser;
             }
             else
             {
                 $available['mobileUser'] = [];
             }
         }
         return $available;
     }
     //登录
     public function loginByUser($user_id,$app_id = 101)
     {
         $oUserService = (new UserService());
         $userInfo = $oUserService->getUserInfo($user_id);
         //用户存在只修改验证码状态及生产token
         if($userInfo->is_del==1){
             $return['msg']  = $this->msgList['mobile_prohibit'];
         }else{
                 //生成token
                 $tokeninfo = $oUserService->getToken($userInfo->user_id);
                 $currentTime = time();
                 //修改用户登录时间
                 $oUserService->updateUserInfo(['last_login_time'=>date('Y-m-d H:i:s',$currentTime),
                     'last_update_time'=>date('Y-m-d H:i:s',$currentTime),
                     'last_login_source'=>$app_id],$userInfo->user_id);
                 $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token']]];
         }
         return $return;
     }
    public function checkMobileAvailable($openid,$mobile,$app_id)
    {
        $oUserService =  new UserService();
        //通过手机号匹配
        $currentUser = $oUserService->getUserInfoByMobile($mobile);
        //找到用户
        if(isset($currentUser->user_id))
        {
            //获取用户关联appid的OpenId
            $openIdList = $oUserService->getOpenIdListByUser($currentUser->user_id,$app_id);
            $openIdInfo = $openIdList[$app_id]??[];
            //如果找到且openid
            if(isset($openIdInfo["open_id"]))
            {
                //相符 识别为本人
                if($openIdInfo["open_id"]== $openid)
                {
                    $return = ['result'=>1,"mobileUser"=>$currentUser,"self"=>1];
                }
                else
                {
                    //测试用户
                    if($currentUser->test==1)
                    {
                        //如果是测试用户，直接通过
                        $return = ['result'=>1,"mobileUser"=>$currentUser,"self"=>0];
                    }
                    else
                    {
                        $return = ['result'=>0,"msg"=>"openid_used"];
                    }
                }
            }
            else//没找到，识别为本人
            {
                $return = ['result'=>1,"mobileUser"=>$currentUser,"self"=>1];
            }
        }
        else
        {
            //通过openid和app_id匹配用户
            $userList = $oUserService->getWechatUserInfoByOpenId($openid,$app_id);
            //找到用户
            if(count($userList)>0)
            {
                $return = ['result'=>0,"msg"=>"openid_used"];
            }
            else//没找到
            {
                //同时匹配不上，返回空用户
                $return = ['result'=>1,"mobileUser"=>[]];
            }
        }
        return $return;
    }
}