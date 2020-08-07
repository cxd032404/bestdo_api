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
        "congratulation"=>"恭喜您，",
        "mobile_empty"=>"手机号无效，请填写正确的手机号码！",
        "password_empty"=>"密码无效，请填写正确的密码！",
        "sendcode_empty"=>"验证码无效，请填写验证码！",
        "activity_empty"=>"活动无效，请选择正确的活动！",
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
        "wechat_used"=>"您所使用微信账号已经绑定了其他的手机号码",
        "wechat_mobile_used"=>"您所使用手机号码已经绑定了其他的微信账号",
        "miniprogram_used"=>"您所使用小程序账号已经绑定了其他的手机号码",
        "miniprogram_mobile_used"=>"您所使用手机号码已经绑定了其他的小程序账号",
    ];

    public function mobileCodeLogin($mobile="",$logincode="",$companyuser_id=0,$code="",$miniProgramUserInfo = "",$app_id = 101)
    {
        //基础校验
        //手机号码/验证码为空校验
        $checkMobile = $this->checkMobileCode($mobile,$logincode);
        if($checkMobile['result']==false)
        {
            return $checkMobile;
        }
        //验证码码有效性校验

        //获取可能可用的手机号码对应用户用以登录

        //找到用户
            //登录流程


        //没找到用户
            //company_user>0
                //登录流程


            //compnay_user=0
                //company_id > 0
                    //登录
                //company_id = 0
                    //company_name ！=""
                        //创建企业
                        //登录流程
                        //更新用户到企业
                    //company_name = ""
                        //拒绝登录
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
     }
}