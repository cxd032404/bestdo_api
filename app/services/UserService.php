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

class UserService extends BaseService
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



    //手机号密码登录方法
    public function mobileLogin($mobile="",$password="")
    {
        $common = new Common();
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if( empty($mobile) || !$common->check_mobile($mobile) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($password)){
            $return['msg']  = $this->msgList['password_empty'];
        }else{
            //查询用户数据
            $userinfo = \HJ\UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','password','username','user_img','company_id']]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_noregister'];
            }else if($userinfo->is_del==1){
                $return['msg']  = $this->msgList['mobile_prohibit'];
            }else if($userinfo->password!=md5($password)){
                $return['msg']  = $this->msgList['password_error'];
            }else{
                //生成token
                $tokeninfo = $this->getToken($userinfo->user_id);
                $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'],'user_token'=>$tokeninfo['token']]];
            }
        }
        return $return;
    }
    //手机号验证码登录方法
    public function mobileCodeLogin($mobile="",$logincode="",$companyuser_id=0,$code="",$miniProgramUserInfo = "",$app_id = 101)
    {
        $common = new Common();
        $oWechatService = (new WechatService());
        $login_code = $this->redis->get('login_'.$mobile);
        //后台配置的测试号码
        $test_phone_number =  (new ConfigService())->getConfig("phoneNumber")->content??'';
        //配置文件中的测试号
        $testMoblie = $this->config->testMoblie;
        if(strstr($test_phone_number,$mobile) || in_array($mobile,(array)$testMoblie)){
            $login_code = json_encode(['code'=>123456]);
        }
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if( empty($mobile) || !$common->check_mobile($mobile) ) {
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($logincode)){
            $return['msg']  = $this->msgList['sendcode_empty'];
        }else if(!$login_code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }else if($logincode != json_decode($login_code)->code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }
        else{
            $available['result'] = 1;
            if(!empty($code))
            {
                //通过code获取到微信的用户信息
                $WechatUserInfo = $oWechatService->getUserInfoByCode_Wechat($this->key_config->tencent,$code,$app_id);
                if(isset($WechatUserInfo['openid']))
                {
                    //检查手机号和微信Openid是否配对组合可用
                    $available = $this->checkMobileAvailable($WechatUserInfo['openid'],$mobile,$app_id);
                    if($available['result']==0)
                    {
                        $return = ['result'=>0,'data'=>[],'msg'=>$this->msgList[$available['msg']],'code'=>400];
                    }
                }
                else
                {
                    $mobileUser = $this->getUserInfoByMobile($mobile,$app_id);
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
                $miniProgramUserInfo = $oWechatService->getUserInfoByCode_mini_program($code,$app_id);
                if(isset($miniProgramUserInfo['openid']))
                {
                    $available = $this->checkMobileAvailable($miniProgramUserInfo['openid'],$mobile,'miniprogram');
                    if($available['result']==0)
                    {
                        $return = ['result'=>0,'data'=>[],'msg'=>$this->msgList[$available['msg']],'code'=>400];
                    }
                }
                else
                {
                    $mobileUser = $this->getUserInfoByMobile($mobile,$app_id);
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
                $mobileUser = $this->getUserInfoByMobile($mobile);
                if(isset($mobileUser->user_id))
                {
                    $available['mobileUser'] =  $mobileUser;
                }
                else
                {
                    $available['mobileUser'] = [];
                }
            }
            if($available['result']==0)
            {
                return $return;
            }

            //查询用户数据
            $userinfo = $available['mobileUser'];
            //如果匹配上用户，就不需要入驻信息了
            if($userinfo->user_id)
            {
                $companyuser_id = 0;
            }
            if(isset($userinfo->user_id))
            {

                    //用户存在只修改验证码状态及生产token
                    if($userinfo->is_del==1){
                        $return['msg']  = $this->msgList['mobile_prohibit'];
                    }else{
                        //修改验证码记录状态
                        $sendcode = $this->setMobileCode($mobile,$logincode);
                        if(!$sendcode){
                            $return['msg']  = $this->msgList['code_status_error'];
                        }else{
                            if(!empty($miniProgramUserInfo))
                            {
                                //如果尚未登录微信信息
                                if($userinfo->mini_program_id=="")
                                {
                                    $this->wechat_code_logger->info("登录更新小程序信息");
                                    //完善用户小程序资料
                                    (new WechatService)->updateUserWithMiniProgram($userinfo->user_id,$miniProgramUserInfo);
                                }
                                else
                                {
                                    if($userinfo->test!=1)
                                    {
                                        $this->wechat_code_logger->info("登录更新小程序信息");
                                        //完善用户小程序资料
                                        (new WechatService)->updateUserWithMiniProgram($userinfo->user_id,$miniProgramUserInfo);
                                    }
                                }

                            }
                            if(!empty($code))
                            {
                                //如果尚未登录微信信息
                                if($userinfo->wechatid=="")
                                {
                                    $this->wechat_code_logger->info("登录更新微信信息");
                                    //完善用户微信资料
                                    (new WechatService)->updateUserWithWechat($this->key_config->wechat,$userinfo->user_id,$code);
                                }
                                else
                                {
                                    if($userinfo->test!=1)
                                    {
                                        $this->wechat_code_logger->info("登录更新微信信息");
                                        //完善用户微信资料
                                        (new WechatService)->updateUserWithWechat($this->key_config->wechat,$userinfo->user_id,$code);
                                    }
                                }
                            }
                            //生成token
                            $tokeninfo = $this->getToken($userinfo->user_id);
                            $currentTime = time();
                            //修改用户登录时间
                            $this->updateUserInfo(['last_login_time'=>date('Y-m-d H:i:s',$currentTime),
                                'last_update_time'=>date('Y-m-d H:i:s',$currentTime),
                                'last_login_source'=>"Mobile"],$userinfo->user_id);
                            $return  = ['result'=>1, 'msg'=>$this->msgList['login_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token']]];
                        }
                    }


            }else{//用户不存在 需创建用户+修改验证码状态+修改企业名单状态+生成token
                try {
                    //启用事务
                    $manager = new TxManager();
                    //指定你需要的数据库
                    $manager->setDbService("hj_user");
                    // Request a transaction
                    $transaction = $manager->get();
                    //查询企业导入名单
                    $companyuserlist = \HJ\CompanyUserList::findFirst([
                        "id=:companyuser_id:",
                        'bind'=>[
                            'companyuser_id'=>$companyuser_id,
                        ],
                        'order'=>'id desc'
                    ]);
                    if(!isset($companyuserlist->id)){
                        $transaction->rollback($this->msgList['companyuser_empty']);
                    }
                    $currentTime = time();
                    $department = (new DepartmentService())->getDepartment($companyuserlist->department_id);
                    //创建用户
                    $user = new \HJ\UserInfo();
                    $user->setTransaction($transaction);
                    $user->username = $mobile;
                    $user->mobile = $mobile;
                    $user->company_id = $companyuserlist->company_id;
                    $user->department_id = $companyuserlist->department_id;
                    $user->department_id_1 = $department['department_id_1'];
                    $user->department_id_2 = $department['department_id_2'];
                    $user->department_id_3 = $department['department_id_3'];
                    $user->worker_id = $companyuserlist->worker_id;
                    $user->true_name = $companyuserlist->name;
                    $user->nick_name = $companyuserlist->name;
                    $user->last_login_time = date("Y-m-d H:i:s",$currentTime);
                    $user->last_update_time = date("Y-m-d H:i:s",$currentTime);
                    $user->last_login_source = "Mobile";
                    if ($user->create() === false) {
                        $transaction->rollback($this->msgList['register_fail']);
                    }
                    if(!empty($miniProgramUserInfo))
                    {
                        $this->wechat_code_logger->info("注册更新小程序信息");
                        //完善用户小程序资料
                        (new WechatService)->updateUserWithMiniProgram($user->user_id,$miniProgramUserInfo);
                    }
                    if(!empty($code))
                    {
                        $this->wechat_code_logger->info("注册更新微信信息");
                        //完善用户微信资料
                        (new WechatService)->updateUserWithWechat($this->key_config->wechat,$user->user_id,$code);
                    }
                    //修改验证码记录状态
                    $sendcode = $this->setMobileCode($mobile,$logincode);
                    if(!$sendcode){
                        $transaction->rollback($this->msgList['code_status_error']);
                    }
                    //修改企业用户名单状态
                    $companyuser = $this->setCompanyUser($companyuserlist->id,$user->user_id);
                    if(!$companyuser){
                        $transaction->rollback($this->msgList['companyuser_update_fail']);
                    }
                    //生成token
                    $tokeninfo = $this->getToken($user->user_id);
                    $return  = ['result'=>1, 'msg'=>$this->msgList['register_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token'] ]];
                    $transaction->commit($return);
                } catch (TxFailed $e) {
                    // 捕获失败回滚的错误
                    $return['msg']  = $e->getMessage();
                }
            }
        }
        return $return;
    }
    //手机号忘记密码方法
    public function mobileForgetPwd($mobile="",$code="",$newpassword="")
    {
        $common = new Common();
        $forget_code = $this->redis->get('forget_'.$mobile);
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
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
            $userinfo = \HJ\UserInfo::findFirst(["username = '".$mobile."'"]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['mobile_noregister'];
            }else if($userinfo->is_del==1){
                $return['msg']  = $this->msgList['mobile_prohibit'];
            }else{
                try {
                    //启用事务
                    $manager = new TxManager();
                    //指定你需要的数据库
                    $manager->setDbService("hj_user");
                    // Request a transaction
                    $transaction = $manager->get();
                    //修改用户密码
                    $userinfo->setTransaction($transaction);
                    if($userinfo->update(['password'=>md5($newpassword)]) === false){
                        $transaction->rollback($this->msgList['changepwd_error']);
                    }
                    //修改验证码记录状态
                    $sendcode = $this->setMobileCode($mobile,$code);
                    if(!$sendcode){
                        $transaction->rollback($this->msgList['code_status_error']);
                    }
                    $this->redis->expire('forget_'.$mobile,0);
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
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
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
            //验证企业用户名单状态
            $companyuserlist = \HJ\CompanyUserList::findFirst(["id=:id:", 'bind'=>['id'=>$company_user_id], 'order'=>'id desc']);
            if(!isset($companyuserlist->id)){
                $return['msg']  = $this->msgList['companyuser_empty'];
            }else{
                //查询用户数据
                $userinfo = \HJ\UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','username','user_img']]);
                if(isset($userinfo->user_id)){
                    $return['msg']  = $this->msgList['mobile_register'];
                    if($userinfo->is_del==1){
                        $return['msg']  = $this->msgList['mobile_prohibit'];
                    }
                }else{
                    try {
                        //启用事务
                        $manager = new TxManager();
                        //指定你需要的数据库
                        $manager->setDbService("hj_user");
                        // Request a transaction
                        $transaction = $manager->get();
                        //添加用户
                        $user = new \HJ\UserInfo();
                        $user->setTransaction($transaction);
                        $user->username = $mobile;
                        $user->password = md5($password);
                        $user->company_id = $companyuserlist->company_id??1;
                        if ($user->create() === false) {
                            $transaction->rollback($this->msgList['register_fail']);
                        }
                        //修改验证码记录状态
                        $sendcode = $this->setMobileCode($mobile,$code);
                        if(!$sendcode){
                            $transaction->rollback($this->msgList['code_status_error']);
                        }
                        //修改企业用户名单状态
                        $companyuser = $this->setCompanyUser($companyuserlist->id,$user->user_id);
                        if(!$companyuser){
                            $transaction->rollback($this->msgList['companyuser_update_fail']);
                        }
                        //生成token
                        $tokeninfo = $this->getToken($user->user_id);
                        $return  = ['result'=>1, 'msg'=>$this->msgList['register_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token']]];
                        $this->redis->expire('forget_'.$mobile,0);
                        $transaction->commit($return);
                    } catch (TxFailed $e) {
                        // 捕获失败回滚的错误
                        $return['msg']  = $e->getMessage();
                    }
                }
            }
        }
        return $return;
    }




    //更新用户信息
    public function updateUserInfo($map,$user_id="")
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        //修改用户信息
        $userinfo = \HJ\UserInfo::findFirst(["user_id = '".$user_id."'"]);
        foreach($map as $key => $value)
        {
            if(!empty($value))
            {
                $userinfo->$key = $value;
            }
        }
        $userinfo->last_update_time = date("Y-m-d H:i:s");
        if ($userinfo->update() === false) {
            $return['msg']  = $this->msgList['update_userinfo_fail'];
        }else {
            $this->getUserInfo($user_id.'*',0);
            $return = ['result' => 1, 'msg' => $this->msgList['update_userinfo_success'], 'code' => 200, 'data' => []];
        }
        return $return;
    }
    //更新用户对应的Openid列表
    public function updateUserOpenId($user_id,$openid,$app_id)
    {
        $openidList = $this->getOpenIdListByUser($user_id,$app_id);
        if(count($openidList)>0)
        {
            return ['result'=>true];
        }
        else
        {
            $userInfo = $this->getUserInfo($user_id,"user_id,mobile,company_id");
            $openId = new \HJ\OpenId();
            $openId->open_id = $openid;
            $openId->user_id = $userInfo->user_id;
            $openId->mobile = $userInfo->mobile;
            $openId->company_id = $userInfo->company_id;
            $openId->app_id = $app_id;
            $openId->create_time = $openId->update_time = date("Y-m-d H:i:s");
            if ($openId->create() === true)
            {
                return ['result'=>true];
            }
            else
            {
                return ['result'=>false];
            }
        }
    }

    //点赞
    public function setKudosInc($post_id=0,$sender_id=0)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        try {
            //启用事务
            $manager = new TxManager();
            //指定你需要的数据库
            $manager->setDbService("hj_user");
            // Request a transaction
            $transaction = $manager->get();
            //查询列表内容
            $posts = \HJ\Posts::findFirst(["post_id='".$post_id."'"]);
            $list_id = $posts->list_id;
            $listInfo = (new ListService())->getListInfo($list_id,"list_id,detail");
            $listInfo->detail = json_decode($listInfo->detail,true);
            if(!isset($posts->post_id)){
                $transaction->rollback($this->msgList['posts_empty']);
            }

            //查询点赞记录
            $postskudos_info = (new PostsService())->checkKudos($sender_id??0,"",$post_id);

            $this->logger->info("点赞".json_encode($listInfo->detail));
            if(isset($postskudos_info->id)){
                $transaction->rollback($this->msgList['posts_'.($listInfo->detail['type']??"kudo").'_exist']);
            }
            //修改点赞次数
            $posts->setTransaction($transaction);
            $posts->kudos = intval($posts->kudos+1);
            if ($posts->update() === false) {
                $transaction->rollback($this->msgList['kudos_fail']);
            }
            (new PostsService())->getPosts($post_id,'*',0);
            $current_time = time();
            //查询用户信息
            $sender_wechatid = '';
            if($sender_id>0) {
                $userInfo = (new UserService())->getUserInfo($sender_id, 'user_id,wechatid');
                $sender_wechatid = $userInfo->wechatid;
            }
            //新增点赞记录
            $postskudos = new \HJ\PostsKudos();
            $postskudos->setTransaction($transaction);
            $postskudos->sender_id = $sender_id;
            $postskudos->receiver_id = $posts->user_id;
            $postskudos->list_id = $posts->list_id;
            $postskudos->post_id = $post_id;
            $postskudos->wechatid = $sender_wechatid;
            $postskudos->date = date("Y-m-d",$current_time);
            $postskudos->create_time = date("Y-m-d H:i:s",$current_time);
            if($postskudos->save() === false){
                $transaction->rollback($this->msgList['posts_kudos_fail']);
            }



            $msg = $this->msgList['congratulation'].$this->kudosTypeList[$listInfo->detail['type']??"vote"].$this->msgList['posts_success'];
            $return  = ['result'=>1, 'msg'=>$msg, 'code'=>200, 'data'=>['kudos'=>intval($posts->kudos)]];
            $transaction->commit($return);
        } catch (TxFailed $e) {
            // 捕获失败回滚的错误
            $return['msg']  = $e->getMessage();
        }
        return $return;
    }

    //取消点赞
    public function setKudosDec($post_id=0,$sender_id=0)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        try {
            //启用事务
            $manager = new TxManager();
            //指定你需要的数据库
            $manager->setDbService("hj_user");
            // Request a transaction
            $transaction = $manager->get();
            //查询列表内容
            $posts = \HJ\Posts::findFirst(["post_id='".$post_id."'"]);
            if(!isset($posts->post_id)){
                $transaction->rollback($this->msgList['posts_empty']);
            }
            //查询点赞记录
            $postskudos = \HJ\PostsKudos::findFirst([
                "sender_id=:sender_id: and post_id=:post_id: and is_del=0 and create_time between :starttime: AND :endtime: ",
                'bind'=>[
                    'sender_id'=>$sender_id,
                    'post_id'=>$post_id,
                    'starttime'=>date('Y-m-d').' 00:00:00',
                    'endtime'=>date('Y-m-d').' 23:59:59',
                ]
            ]);
            if(!isset($postskudos->id)){
                $transaction->rollback($this->msgList['posts_kudos_noexist']);
            }
            //修改点赞次数
            $posts->setTransaction($transaction);
            $posts->kudos = intval($posts->kudos-1);
            if ($posts->update() === false) {
                $transaction->rollback($this->msgList['posts_remove_fail']);
            }
            //删除点赞记录
            $postskudos->setTransaction($transaction);
            $postskudos->is_del = 1;
            if($postskudos->update() === false){
                $transaction->rollback($this->msgList['posts_kudos_update_fail']);
            }
            $return  = ['result'=>1, 'msg'=>$this->msgList['posts_remove_success'], 'code'=>200, 'data'=>['kudos'=>intval($posts->kudos)]];
            $transaction->commit($return);
        } catch (TxFailed $e) {
            // 捕获失败回滚的错误
            $return['msg']  = $e->getMessage();
        }
        return $return;
    }

    //隐藏活动记录
    public function setActivityPosts($post_id=0,$user_id=0)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        try {
            //启用事务
            $manager = new TxManager();
            //指定你需要的数据库
            $manager->setDbService("hj_user");
            // Request a transaction
            $transaction = $manager->get();
            //查询列表内容
            $posts = \HJ\Posts::findFirst(["post_id='".$post_id."' and user_id='".$user_id."' "]);
            if(!isset($posts->post_id)){
                $transaction->rollback($this->msgList['posts_empty']);
            }
            //修改状态
            $posts->setTransaction($transaction);
            $posts->visible = 2;
            if ($posts->update() === false) {
                $transaction->rollback($this->msgList['posts_delete_fail']);
            }
            $return  = ['result'=>1, 'msg'=>$this->msgList['posts_del_success'], 'code'=>200, 'data'=>[]];
            $transaction->commit($return);
        } catch (TxFailed $e) {
            // 捕获失败回滚的错误
            $return['msg']  = $e->getMessage();
        }
        return $return;
    }

    //修改验证码状态
    public function setMobileCode($mobile,$code){
        //修改验证码记录状态
        $sendcode = \HJ\SendCode::findFirst([
            "to=:to: and type='mobile' and status=1 and code=:code:",
            'bind'=>['to'=>$mobile, 'code'=>$code], 'order'=>'id desc'
        ]);
        if(isset($sendcode->id)){
            if ($sendcode->update(['status'=>0]) === false) {
                return false;
            }
        }
        $this->redis->expire('login_'.$mobile,0);
        return true;
    }

    //修改企业名单状态
    public function setCompanyUser($companyuser_id,$user_id){
        //查询企业导入名单
        $companyuserlist = \HJ\CompanyUserList::findFirst(["id=:id:", 'bind'=>['id'=>$companyuser_id], 'order'=>'id desc']);
        if(isset($companyuserlist->id)){
            //修改企业用户名单状态
            if ($companyuserlist->update(['user_id'=>$user_id,'update_time'=>date('Y-m-d H:i:s',time())]) === false) {
                return false;
            }
        }
        return true;
    }

    //获取用户token
    public function getToken($user_id){
        $userinfo = $this->getUserInfo($user_id,"*",0);
        $company_name = "";
        if(isset($userinfo->company_id)){
            $configcompany = \HJ\Company::findFirst([
                "company_id=:company_id:",
                'bind'=>['company_id'=>$userinfo->company_id], 'order'=>'company_id desc'
            ]);
            if(isset($configcompany->company_id)){
                $company_name = $configcompany->company_name;
            }
        }
        $map = [
            'user_id'=>$userinfo->user_id??0,
            'username'=>$userinfo->username??"",
            'nick_name'=>$userinfo->nick_name??"",
            'true_name'=>$userinfo->true_name??"",
            'user_img'=>$userinfo->user_img??"",
            'mobile'=>$userinfo->mobile??"",
            'company_id'=>$userinfo->company_id??0,
            'department_id'=>$userinfo->department_id??0,
            'department_id_1'=>$userinfo->department_id_1??0,
            'department_id_2'=>$userinfo->department_id_2??0,
            'department_id_3'=>$userinfo->department_id_3??0,
            'company_name'=>$company_name,
            'worker_id'=>$userinfo->worker_id??"",
            'last_login_time'=>$userinfo->last_login_time??"",
            'manager_id'=>$userinfo->manager_id??0,
            'expire_time'=>time()+$this->config->cache_settings->wechat_code->expire,
        ];
        $oJwt = new ThirdJwt();
        $data['map'] = $map;
        $data['token'] = $oJwt::getToken($map);
        return $data;
    }

    //用户token解密
    public function getDecrypt()
    {
        $return  = ['result'=>0, 'msg'=>$this->msgList['decrypt_fail'], 'code'=>403, 'data'=>[]];
        $user_token = $this->request->get("UserToken")??$this->request->getHeader('UserToken');
        $user_token = $user_token?preg_replace('# #','',$user_token):"";
        $oJwt = new ThirdJwt();
        $user_info = $oJwt::getUserId($user_token);
        if(isset($user_info)){
            $user_info = json_decode($user_info);
            if($user_info->expire_time<time()){
                $return['msg'] = $this->msgList['user_token_invalid'];
            }else{
                $return  = ['result'=>1, 'msg'=>$this->msgList['decrypt_success'], 'code'=>200, 'data'=>['user_info'=>$user_info]];
            }
        }
        return $return;
    }

    //用户token解密
    public function verifyTokenForPage($company="",$page_sign="")
    {
        //$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjNmMmc1N2E5MmFhIn0.eyJpc3MiOiJodHRwOlwvXC9hZG1pbmFwaS5weWcuY29tIiwiYXVkIjoiaHR0cDpcL1wvd3d3LnB5Zy5jb20iLCJqdGkiOiIzZjJnNTdhOTJhYSIsImlhdCI6MTU5MDY2NjYyOCwibmJmIjoxNTkwNjY2NjI3LCJleHAiOjE1OTMyNTg2MjgsImRhdGEiOiJ7XCJ1c2VyX2lkXCI6XCIxMTg3OVwiLFwidXNlcm5hbWVcIjpcIjE3MDgyMTcwNzg3XCIsXCJuaWNrX25hbWVcIjpcIlxcdTZkNGJcXHU4YmQ1MVwiLFwidHJ1ZV9uYW1lXCI6XCJcXHU2ZDRiXFx1OGJkNVxcdTc1MjhcXHU2MjM3MVwiLFwidXNlcl9pbWdcIjpcIlwiLFwibW9iaWxlXCI6XCJcIixcImNvbXBhbnlfaWRcIjpcIjFcIixcImNvbXBhbnlfbmFtZVwiOlwiXCIsXCJ3b3JrZXJfaWRcIjpcIlwiLFwibGFzdF9sb2dpbl90aW1lXCI6XCIyMDIwLTA0LTI2IDEzOjUyOjU5XCIsXCJtYW5hZ2VyX2lkXCI6XCIyXCIsXCJleHBpcmVfdGltZVwiOjE1OTMyNTg2Mjh9In0.j5xto9UrqMoXHLWfozhSyBnffGTP3ZqReGtZ7e2hBVw";
        $return  = ['result'=>1, 'msg'=>$this->msgList['decrypt_success'], 'code'=>200, 'data'=>[]];
        $user_token = $this->request->get("UserToken")??$this->request->getHeader('UserToken');
        $user_token = $user_token?preg_replace('# #','',$user_token):"";
        $oJwt = new ThirdJwt();
        $user_info = $oJwt::getUserId($user_token);
        if(!isset($user_info) || (isset($user_info) && json_decode($user_info)->expire_time<time())){
            $page_info = (new PageService)->getPageInfoBySign($company,$page_sign,"page_id,need_login");
            if(isset($page_info->need_login) && $page_info->need_login ==1){
                $return  = ['result'=>0, 'msg'=>$this->msgList['decrypt_fail'], 'code'=>403, 'data'=>[]];
            }
        }
        else
        {
            $user_info = json_decode($user_info);
            //缓存数据
            $user_cache_info = $this->getUserInfo($user_info->user_id,'*');
            //合并
            $return['data']  = array_merge((array)$user_info,(array)$user_cache_info);
            if(trim($return['data']['user_img'])=="")
            {
                $userImg = (new ConfigService())->getConfig("default_user_img");
                $userImg->content = json_decode($userImg->content,true);
                $userImg = $userImg->content['0']['img_url']??"";
                $return['data']['user_img'] = $userImg;
            }
        }
        return $return;
    }

    //后台获取用户token
    public function createTokenForManager($manager_id){
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if($manager_id==0){
            $return['msg']  = $this->msgList['manager_id_error'];
        }else{
            $userinfo = \HJ\UserInfo::findFirst([
                "manager_id=:manager_id:",
                'bind'=>['manager_id'=>$manager_id],
                'order'=>'user_id desc'
            ]);
            if(!isset($userinfo->user_id)){
                $return['msg']  = $this->msgList['manager_id_invalid'];
            }else{
                $tokeninfo = $this->getToken($userinfo->user_id);
                $return  = ['result'=>1, 'msg'=>$this->msgList['token_for_manager_success'], 'code'=>200, 'data'=>['user_token'=>$tokeninfo['token']]];
            }
        }
        return $return;
    }

    public function searchForCompanyUser($params)
    {
        $searchParams = [
            "company_id = '".$params['company_id']."' and ((name like '%".$params['query']."%') or (mobile like '%".$params['query']."%') or (worker_id like '%".$params['query']."%'))",
            "columns" => "*",
            "order" => "name desc",
            "limit" => ["offset"=>($params['page']-1)*$params['page_size'],"number"=>$params['page_size']]
        ];
        $userList = \HJ\CompanyUserList::find($searchParams);
        return $userList;
    }

    //验证用户身份
    public function checkoutCompany($company_id=0,$worker_id="",$name="")
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if(intval($company_id)<=0) {
            $return['msg']  = $this->msgList['company_id_empty'];
        }else if(empty($worker_id)){
            $return['msg']  = $this->msgList['worker_id_empty'];
        }else if(empty($name)){
            $return['msg']  = $this->msgList['name_empty'];
        }else{
            $companyuser = \HJ\CompanyUserList::findFirst([
                "company_id='".$company_id."' and worker_id='".$worker_id."' and name='".$name."' ",
                "columns" => "*",
                "order" => "id desc"
            ]);
            if(!isset($companyuser->id)){
                $return['msg'] = $this->msgList['company_user_error'];
            }else{
                $return  = ['result'=>1, 'msg'=>$this->msgList['company_user_success'], 'code'=>200, 'data'=>['companyuser'=>$companyuser]];
            }
        }
        return $return;
    }

    /*
     * 获取我的活动记录
     * 参数
     * post_id（必填）：作品id数组
     * */
    public function getPostByActivityAction($post_id=[],$page=1,$pageSize=1)
    {
        $post_list = [];
        $count = 0;
        if($post_id){
            if(is_array($post_id))
            {
                $params =             [
                    "post_id in (".implode(",",$post_id).")"." "." and visible=1",
                    "columns" => "post_id,list_id,user_id,company_id,content,source,create_time,views,kudos,visible",
                    "order" => "post_id desc",
                    "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
                ];
                $params_count = [
                    "post_id in (".implode(",",$post_id).")"." "." and visible=1",
                    "columns" => "count(1) as count",
                ];
            }
            else
            {
                $params =             [
                    "post_id = ".$post_id." "." and visible=1",
                    "columns" => "post_id,list_id,user_id,company_id,content,source,create_time,views,kudos,visible",
                    "order" => "post_id desc",
                    "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
                ];
                $params_count = [
                    "post_id = ".$post_id." "." and visible=1",
                    "columns" => "count(1) as count",
                ];
            }
            $post_list = (new \HJ\Posts())->find($params)->toArray();
            foreach($post_list as $k=>$v){
                $post_list[$k]['activity_name'] = "";
                $list = \HJ\ListModel::findFirst([
                    "list_id='".$v['list_id']."'",
                ])->toArray();
                if(isset($list['list_id'])){
                    $list['detail'] = json_decode($list['detail'],true);
                    $activity = \HJ\Activity::findFirst([
                        "activity_id='".$list['activity_id']."'",
                    ])->toArray();
                    if(isset($activity['activity_id'])){
                        $post_list[$k]['activity_name'] = $activity['activity_name'];
                    }
                }
                $post_list[$k]['list_type'] = $list['detail']['type']??"vote";
                $post_list[$k]['source'] = json_decode($post_list[$k]['source'],true);
                $post_list[$k]['source'] = (new UploadService())->parthSource($post_list[$k]['source']);
                $post_list[$k]['user_info'] = $this->getUserInfo($v['user_id']);
            }
            $count = (new \HJ\Posts())->findFirst($params_count)['count']??0;
        }
        $return  = ['data'=>$post_list,'count'=>$count,'total_page'=>ceil($count/$pageSize)];
        return $return;
    }

    //获取用户信息
    public function getUserInfo($user_id,$columns = 'user_id,company_id,nick_name,true_name,user_img,is_del',$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->user_info;
        $cacheName = $cacheSetting->name.$user_id;
        $params =             [
            "user_id='".$user_id."' and is_del=0",
            'columns'=>'*',
        ];
        if($cache == 0)
        {
            //获取列表作者信息
            $userInfo = \HJ\UserInfo::findFirst($params);
            if(isset($userInfo->user_id))
            {
                $this->redis->set($cacheName,json_encode($userInfo));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($cache->user_id))
            {
                $userInfo = $cache;
            }
            else
            {
                //获取列表作者信息
                $userInfo = \HJ\UserInfo::findFirst($params);
                if(isset($userInfo->user_id))
                {
                    $this->redis->set($cacheName,json_encode($userInfo));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        if(!isset($userInfo->department_id_1) || $userInfo->department_id_1==0)
        {
            $this->fixUserDepartment($user_id);
        }
        $userInfo = json_decode(json_encode($userInfo),true);
        if($columns != "*")
        {
            $t = explode(",",$columns);
            $userInfo = json_decode(json_encode($userInfo),true);
            foreach($userInfo as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($userInfo[$key]);
                }
            }
        }
        $userInfo = json_decode(json_encode($userInfo));
        //有user_img自段且为null
        if(isset($userInfo->user_img)&&$userInfo->user_img==='')
        {
            $userImg = (new ConfigService())->getConfig("default_user_img");
            $userImg->content = json_decode($userImg->content,true);
            $userImg = $userImg->content['0']['img_url']??"";
            $userInfo->user_img = $userImg;
        }
        return $userInfo;
    }
    public function getUserInfoByMobile($mobile = "")
    {
        //获取列表作者信息
        $userInfo = \HJ\UserInfo::findFirst([
            "mobile='".$mobile."'",
            'columns'=>'*',
        ]);
        if(isset($userInfo->user_id))
        {
            return $userInfo;
        }
        else
        {
            return [];
        }
    }
    public function getUserInfoByWechat($openId = "")
    {
        //获取列表作者信息
        $userInfo = \HJ\UserInfo::findFirst([
            "wechatid='".$openId."'",
            'columns'=>'*',
        ]);
        if(isset($userInfo->user_id))
        {
            return $userInfo;
        }
        else
        {
            return [];
        }
    }
    public function getWechatUserInfoByOpenId($openId = "",$app_id = 0)
    {
        if($app_id>0)
        {
            $params = [
                "open_id = '".$openId."' and app_id = '".$app_id."'",
                'columns'=>'*',
            ];
        }
        else
        {
            $params = [
                "open_id = '".$openId."'",
                'columns'=>'*',
            ];
        }
        //获取用户信息
        $userList = \HJ\OpenId::find($params);
        $return = [];
        foreach($userList as $key => $value)
        {
            $return[$value->app_id] = $value->toArray();
        }
        return $return;
    }
    //获取用户关联的openid列表
    public function getOpenIdListByUser($user_id,$app_id = 0)
    {
        if($app_id>0)
        {
            $params = [
                "user_id = '".$user_id."' and app_id = '".$app_id."'",
                'columns'=>'*',
            ];
        }
        else
        {
            $params = [
                "user_id = '".$user_id."'",
                'columns'=>'*',
            ];
        }
        //获取用户信息
        $openIdList= \HJ\OpenId::find($params);
        $return = [];
        foreach($openIdList as $key => $value)
        {
            $return[$value->app_id] = $value->toArray();
        }
        return $return;
    }

    //根据微信的unionid获取用户信息
    public function getUserInfoByUnionId($unionId = "")
    {
        if($unionId=="")
        {
            return [];
        }
        //获取列表作者信息
        $userInfo = \HJ\UserInfo::findFirst([
            "unionid='".$unionId."' and is_del=0",
            'columns'=>'*',
        ]);
        if(isset($userInfo->user_id))
        {
            return $userInfo;
        }
        else
        {
            return [];
        }
    }
    //根据小程序的openid获取用户信息
    public function getUserInfoByMiniprogramId($miniprogramId = "")
    {
        //获取列表作者信息
        $userInfo = \HJ\UserInfo::findFirst([
            "mini_program_id='".$miniprogramId."'",
            'columns'=>'*',
        ]);
        if(isset($userInfo->user_id))
        {
            return $userInfo;
        }
        else
        {
            return [];
        }
    }

    public function fixUserDepartment($user_id = 0)
    {
        if($user_id>0)
        {
            $params = [
                "user_id='".$user_id."'",
                'columns'=>'*',
            ];
        }
        else
        {
            $params = [
                "department_id >0 and department_id_1 = 0",
                'columns'=>'*',
            ];
        }
        //获取列表作者信息
        $userList = \HJ\UserInfo::find($params);
        foreach($userList as $userInfo)
        {
            if($userInfo->department_id_1==0 && $userInfo->department_id>0)
            {
                $department = (new DepartmentService())->getDepartment($userInfo->department_id);
                $this->updateUserInfo(['department_id_1'=>$department['department_id_1'],'department_id_2'=>$department['department_id_2'],'department_id_3'=>$department['department_id_3']],$userInfo->user_id);
                $userInfo = $this->getUserInfo($userInfo->user_id,"*",0);
            }
        }

    }
    //获取用户信息
    public function getUserCountByDepartment($company_id,$department_id,$cache = 1)
    {
        $departmentService = new DepartmentService();
        $cacheSetting = $this->config->cache_settings->department_user_count;
        $cacheName = $cacheSetting->name.$department_id;
        if($department_id > 0)
        {
            $department = $departmentService->getDepartment($department_id);
            //$departmentInfo = $departmentService->getDepartmentInfo($department_id,"department_id,company_id");
            $params = [
                "company_id = ".$company_id." and department_id_".$department['current_level']." =".$department_id." and is_del=0",
                'columns'=>'count(user_id) as userCount',
            ];
        }
        else
        {
            $params = [
                "company_id = ".$company_id." and is_del=0",
                'columns'=>'count(user_id) as userCount',
            ];
        }
        if($cache == 0)
        {
            //用户数量
            $userCount = \HJ\UserInfo::findFirst($params);
            if(isset($userCount->userCount))
            {
                $this->redis->set($cacheName,json_encode($userCount));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($cache->userCount))
            {
                $userCount = $cache;
            }
            else
            {
                //用户数量
                $userCount = \HJ\UserInfo::findFirst($params);
                if(isset($userCount->userCount))
                {
                    $this->redis->set($cacheName,json_encode($userCount));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        return $userCount->userCount;
    }

    public function generateTestUser($company_id = 1,$count = 100)
    {
        $departmentService = new DepartmentService();
        $configService = new ConfigService();
        $departmentList = $departmentService->getDepartmentByCompany($company_id)->toArray();
        $currentTime = time();
        $userImg = $configService->getConfig("default_user_img");
        $userImg->content = json_decode($userImg->content,true);
        $userImg = $userImg->content['0']['img_url']??"";
        $success = $fail = 0;
        for($i = 1;$i<=$count;$i++)
        {
            $randName = date("mdHi",$currentTime).sprintf("%05d",$i);
            $randMobile = "1".sprintf("%010d",rand(1,9999999999));
            $randDepartment = $departmentList[array_rand($departmentList)];
            $userInfo = [
            "company_id"=>$company_id,
            "true_name"=>$randName,
            "nick_name"=>"昵称".$randName,
            "department_id" => $departmentList[array_rand($departmentList)]['department_id'],
            "mobile" => $randMobile,
            "username"=>$randMobile,
            "sex"=>rand(1,99)%3,
                "user_img"=>$userImg,
                "reg_time"=>date("Y-m-d H:i:s"),
                "last_update_time"=>date("Y-m-d H:i:s"),
                "last_login_time"=>date("Y-m-d H:i:s"),
                "last_login_source"=>"test",
                "password"=>"",
                "is_delete"=>0,
            ];
            $department = $departmentService->getDepartment($userInfo['department_id']);
            unset($department['current_level']);
            $userInfo = array_merge($userInfo,$department);
            $user = new \HJ\UserInfo();
            foreach($userInfo as $key => $value)
            {
                $user->$key = $value;
            }
            try{
                //print_R($user);
                if ($user->create() === true) {
                    echo "success\n";
                    $success++;
                }
                else
                {
                    foreach($user->getMessages() as $message)
                    {
                        echo "message";
                        print_R($message);
                    }
                    echo "fail\n";
                    $fail++;
                }
                echo "success:".$success."fail:".$fail;
            }
            catch (\Phalcon\Exception $e) {
                echo "<pre>"; print_r( $e->getMessage() );exit;
            }
        }
    }

    public function checkMobileAvailable($openid,$mobile,$type="wechat")
    {
        if($type=="wechat")
        {
            //查找当前微信信息绑定的用户
            $currentWechatUser = $this->getUserInfoByWechat($openid);
        }
        else
        {
            //查找当前小程序信息绑定的用户
            $currentWechatUser = $this->getUserInfoByMiniprogramId($openid);
        }
        if(isset($currentWechatUser->user_id))
        {
            //手机号匹配
            if($currentWechatUser->mobile == $mobile)
            {
                $currentMobileUser = $this->getUserInfoByMobile($mobile);
                $return = ['result'=>1,"mobileUser"=>$currentMobileUser];

            }
            else//不匹配，拒绝登录
            {
                //通过手机号获取用户
                $currentMobileUser = $this->getUserInfoByMobile($mobile);
                //如果是测试用户
                if($currentMobileUser->test==1)
                {
                    //如果微信号不一致，但是手机对用用户是测试用户
                    $return = ['result'=>1,"mobileUser"=>$currentMobileUser];
                }
                else
                {
                    $return = ['result'=>0,"msg"=>$type."_used"];
                }
            }
        }
        else
        {
            //通过手机号获取用户
            $currentMobileUser = $this->getUserInfoByMobile($mobile);
            {
                //手机号匹配不上用户
                if(!isset($currentMobileUser->user_id))
                {
                    //同时匹配不上，返回空用户
                    $return = ['result'=>1,"mobileUser"=>[]];
                }
                else
                {
                    //返回当前手机用户
                    $return = ['result'=>1,"mobileUser"=>$currentMobileUser];
                }
            }
        }
        return $return;
    }

    /*
 * 保存官网预留手机号
 */
    public function phoneNumberSave($phone_number)
    {

            if($phone_number == '')
            {
                $return = ['result' => 0, 'data' => [], 'msg' => "请输入手机号", 'code' => 400];
                return $return;
            }
            $website_mobile = new \HJ\WebsiteMobile();
            $website_mobile->phone_number = $phone_number;
            $website_mobile->create_time  = date('Y-m-d H:i:s',time());
            $website_mobile->status = 1;
            $website_mobile->detail = '';
            $res = $website_mobile->create();
            if($res)
            {
                $return = ['result' => 1, 'data' => [], 'msg' => "保留手机号成功，静待来电", 'code' => 200];
            }else
            {
                $return = ['result' => 0, 'data' => [], 'msg' => "保留手机号失败，请重试", 'code' => 400];
            }
        return $return;
        }
    //创建用户
    public function createUser($userInfo = [])
    {
        $user = new \HJ\UserInfo();
        foreach($userInfo as $key => $value)
        {
            $user->$key = $value;
        }
        $user->create_time = $user->update_time = date("Y-m-d H:i:s");
        if ($user->create() === true) {
            return ['result'=>true,"userInfo"=>$user];
        }
        else
        {
            return ['result'=>false];
        }

    }
}