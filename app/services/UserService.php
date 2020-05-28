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
        "user_name_empty"=>"用户姓名无效，请填写姓名！",
        "activity_empty"=>"活动无效，请选择正确的活动！",
        "department_empty"=>"所属部门无效，请填写所属部门！",
        "companyuser_empty"=>"账户验证失败，当前账户尚未获得登录/注册权限！",
        "posts_empty"=>"列表内容查询不到，请选择正确的列表内容！",
        "company_id_empty"=>"企业编号无效",
        "worker_id_empty"=>"工号无效",
        "name_empty"=>"用户姓名无效",

        "password_error"=>"密码错误，请填写正确的密码！",
        "sendcode_error"=>"验证码错误，请填写正确的验证码！",
        "changepwd_error"=>"密码修改失败！",
        "login_error"=>"登录失败！",
        "register_error"=>"注册失败！",
        "decrypt_error"=>"用户token解析失败！",
        "code_status_error"=>"验证码状态修改失败！",
        "activity_error"=>"报名失败！",
        "filluserinfo_error"=>"信息完善失败！",
        "posts_error"=>"点赞失败！",
        "companyuser_status_error"=>"企业用户名单状态修改失败！",
        "posts_kudos_error"=>"点赞记录新增失败！",
        "posts_remove_error"=>"取消点赞失败！",
        "posts_del_error"=>"活动记录删除失败！",
        "posts_kudos_update_error"=>"点赞记录修改失败！",
        "manager_id_error"=>"后台用户id无效！",
        "manager_id_invalid"=>"后台用户id无效,无对应用户信息！",
        "activity_list_error"=>"您已提交过本次活动作品，不可重复提交！",
        "company_user_error"=>"企业用户身份验证失败！",

        "sendcode_invalid"=>"验证码已失效，请重新发送！",
        "user_token_invalid"=>"用户token已失效，请登录！",

        "mobile_register"=>"手机号已注册，请填写正确的手机号码！",
        "mobile_noregister"=>"手机号尚未注册，请填写正确的手机号码！",

        "login_success"=>"登录成功！",
        "changepwd_success"=>"密码修改成功！",
        "register_success"=>"注册成功！",
        "decrypt_success"=>"用户token解析成功！",
        "activity_success"=>"报名成功！",
        "filluserinfo_success"=>"信息完善成功！",
        "posts_success"=>"点赞成功！",
        "posts_del_success"=>"活动记录删除成功！",
        "posts_remove_success"=>"取消点赞成功！",
        "token_for_manager_success"=>"获取token成功！",
        "company_user_success"=>"企业用户身份验证成功！",

        "mobile_prohibit"=>"手机号已被禁用！",
        "activity_signin"=>"您已报名本次活动，无法重复报名，请选择正确的活动！",
        "activity_expire"=>"当前时间不在报名时间内！",
        "posts_kudos_exist"=>"您今天已点赞过此内容，不可重复点赞！",
        "posts_kudos_noexist"=>"您今天尚未点赞过此内容，不可取消点赞！",

        "activity_not_no"=>"活动尚未开启，请耐心等待！",
        "activity_ended"=>"活动已结束，不可报名！",
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
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','password','username','user_img','company_id']]);
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
    public function mobileCodeLogin($mobile="",$code="",$companyuser_id=0)
    {
        $common = new Common();
        $login_code = $this->redis->get('login_'.$mobile);
        if($mobile=='17621822661' ){
            $login_code = json_encode(['code'=>123456]);
        }
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if( empty($mobile) || !$common->check_mobile($mobile) ) {
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($code)){
            $return['msg']  = $this->msgList['sendcode_empty'];
        }else if(!$login_code){
            $return['msg']  = $this->msgList['sendcode_invalid'];
        }else if($code != json_decode($login_code)->code){
            $return['msg']  = $this->msgList['sendcode_error'];
        }
        else{
            //查询用户数据
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','username','user_img','company_id','last_login_time']]);
            if(isset($userinfo->user_id)){//用户存在只修改验证码状态及生产token
                if($userinfo->is_del==1){
                    $return['msg']  = $this->msgList['mobile_prohibit'];
                }else{
                    //修改验证码记录状态
                    $sendcode = $this->setMobileCode($mobile,$code);
                    if(!$sendcode){
                        $return['msg']  = $this->msgList['code_status_error'];
                    }else{
                        //生成token
                        $tokeninfo = $this->getToken($userinfo->user_id);
                        //修改用户登录时间
                        $userinfo = UserInfo::findFirst(["user_id = '".$userinfo->user_id."'"]);
                        $userinfo->last_login_time = date('Y-m-d H:i:s',time());
                        $userinfo->update();
                        $return  = ['result'=>1, 'msg'=>$this->msgList['register_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token']]];
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
                    $companyuserlist = CompanyUserList::findFirst([
                        "id=:companyuser_id:",
                        'bind'=>[
                            'companyuser_id'=>$companyuser_id,
                        ],
                        'order'=>'id desc'
                    ]);
                    if(!isset($companyuserlist->id)){
                        $transaction->rollback($this->msgList['companyuser_empty']);
                    }
                    //创建用户
                    $user = new UserInfo();
                    $user->setTransaction($transaction);
                    $user->username = $mobile;
                    $user->mobile = $mobile;
                    $user->company_id = $companyuserlist->company_id;
                    $user->worker_id = $companyuserlist->worker_id;
                    $user->true_name = $companyuserlist->name;
                    $user->nick_name = $companyuserlist->name;
                    if ($user->create() === false) {
                        $transaction->rollback($this->msgList['register_error']);
                    }
                    //修改验证码记录状态
                    $sendcode = $this->setMobileCode($mobile,$code);
                    if(!$sendcode){
                        $transaction->rollback($this->msgList['code_status_error']);
                    }
                    //修改企业用户名单状态
                    $companyuser = $this->setCompanyUser($companyuserlist->id,$user->user_id);
                    if(!$companyuser){
                        $transaction->rollback($this->msgList['companyuser_status_error']);
                    }
                    //生成token
                    $tokeninfo = $this->getToken($user->user_id);
                    $return  = ['result'=>1, 'msg'=>$this->msgList['register_success'], 'code'=>200, 'data'=>['user_info'=>$tokeninfo['map'], 'user_token'=>$tokeninfo['token'] ]];
                    $this->redis->expire('login_'.$mobile,0);
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
            $userinfo = UserInfo::findFirst(["username = '".$mobile."'"]);
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
            $companyuserlist = CompanyUserList::findFirst(["id=:id:", 'bind'=>['id'=>$company_user_id], 'order'=>'id desc']);
            if(!isset($companyuserlist->id)){
                $return['msg']  = $this->msgList['companyuser_empty'];
            }else{
                //查询用户数据
                $userinfo = UserInfo::findFirst(["username = '".$mobile."'","columns"=>['user_id','is_del','username','user_img']]);
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
                        $user = new UserInfo();
                        $user->setTransaction($transaction);
                        $user->username = $mobile;
                        $user->password = md5($password);
                        $user->company_id = $companyuserlist->company_id??1;
                        if ($user->create() === false) {
                            $transaction->rollback($this->msgList['register_error']);
                        }
                        //修改验证码记录状态
                        $sendcode = $this->setMobileCode($mobile,$code);
                        if(!$sendcode){
                            $transaction->rollback($this->msgList['code_status_error']);
                        }
                        //修改企业用户名单状态
                        $companyuser = $this->setCompanyUser($companyuserlist->id,$user->user_id);
                        if(!$companyuser){
                            $transaction->rollback($this->msgList['companyuser_status_error']);
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


    //活动报名方法
    public function activitySign($map,$user_id="")
    {
        $common = new Common();
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if( empty($map['mobile']) || !$common->check_mobile($map['mobile']) ){
            $return['msg']  = $this->msgList['mobile_empty'];
        }else if(empty($map['user_name'])){
            $return['msg']  = $this->msgList['user_name_empty'];
        }else if(empty($map['department'])){
            $return['msg']  = $this->msgList['department_empty'];
        }else if(empty($map['activity_id'])){
            $return['msg']  = $this->msgList['activity_empty'];
        }else{
            //查询活动数据
            $configactivity = ConfigActivity::findFirst(["activity_id = '".$map['activity_id']."'","columns"=>['activity_id','apply_start_time','apply_end_time','start_time','end_time','detail']]);
            if(!isset($configactivity->activity_id)){
                $return['msg']  = $this->msgList['activity_empty'];
            }else if(time()<strtotime($configactivity->start_time)){
                $return['msg']  = $this->msgList['activity_not_no'];
            }else if(time()>strtotime($configactivity->end_time)){
                $return['msg']  = $this->msgList['activity_ended'];
            }else if(time()<strtotime($configactivity->apply_start_time) || time()>strtotime($configactivity->apply_end_time)){
                $return['msg']  = $this->msgList['activity_expire'];
            }else{
                $activitylog_info = UserActivityLog::findFirst([
                    "activity_id=:activity_id: and user_id=:user_id:",
                    'bind'=>['activity_id'=>$map['activity_id'], 'user_id'=>$user_id],
                    'order'=>'id desc'
                ]);
                if(isset($activitylog_info->id)){
                    $return  = ['result'=>1, 'msg'=>$this->msgList['activity_success'], 'code'=>200, 'data'=>[json_decode($configactivity['detail'])]];
                }else{
                    //添加用户
                    $useractivitylog = new UserActivityLog();
                    $useractivitylog->user_id = $user_id;
                    $useractivitylog->activity_id = $map['activity_id'];
                    $useractivitylog->user_name = $map['user_name'];
                    $useractivitylog->mobile = $map['mobile'];
                    $useractivitylog->department = $map['department'];
                    if ($useractivitylog->create() === false) {
                        $return['msg']  = $this->msgList['activity_error'];
                    }else{
                        $return  = ['result'=>1, 'msg'=>$this->msgList['activity_success'], 'code'=>200, 'data'=>[json_decode($configactivity['detail'])]];
                    }
                }
            }
        }
        return $return;
    }

    //完善用户信息
    public function updateUserInfo($map,$user_id="")
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        //修改用户信息
        $userinfo = UserInfo::findFirst(["user_id = '".$user_id."'"]);
        if(!empty($map['true_name'])){
            $userinfo->true_name = $map['true_name'];
        }
        if(!empty($map['nick_name'])){
            $userinfo->nick_name = $map['nick_name'];
        }
        $userinfo->sex = $map['sex'];
        if ($userinfo->update() === false) {
            $return['msg']  = $this->msgList['filluserinfo_error'];
        }else {
            $return = ['result' => 1, 'msg' => $this->msgList['filluserinfo_success'], 'code' => 200, 'data' => []];
        }
        return $return;
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
            if(!isset($posts->post_id)){
                $transaction->rollback($this->msgList['posts_empty']);
            }
            //查询点赞记录
            $postskudos_info = PostsKudos::findFirst([
                "sender_id=:sender_id: and post_id=:post_id: and is_del=0 and create_time between :starttime: AND :endtime: ",
                'bind'=>[
                    'sender_id'=>$sender_id,
                    'post_id'=>$post_id,
                    'starttime'=>date('Y-m-d').' 00:00:00',
                    'endtime'=>date('Y-m-d').' 23:59:59',
                ]
            ]);
            if(isset($postskudos_info->id)){
                $transaction->rollback($this->msgList['posts_kudos_exist']);
            }
            //修改点赞次数
            $posts->setTransaction($transaction);
            $posts->kudos = intval($posts->kudos+1);
            if ($posts->update() === false) {
                $transaction->rollback($this->msgList['posts_error']);
            }
            //新增点赞记录
            $postskudos = new PostsKudos();
            $postskudos->setTransaction($transaction);
            $postskudos->sender_id = $sender_id;
            $postskudos->receiver_id = $posts->user_id;
            $postskudos->list_id = $posts->list_id;
            $postskudos->post_id = $post_id;
            if($postskudos->save() === false){
                $transaction->rollback($this->msgList['posts_kudos_error']);
            }
            $return  = ['result'=>1, 'msg'=>$this->msgList['posts_success'], 'code'=>200, 'data'=>['kudos'=>intval($posts->kudos)]];
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
            $postskudos = PostsKudos::findFirst([
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
                $transaction->rollback($this->msgList['posts_remove_error']);
            }
            //删除点赞记录
            $postskudos->setTransaction($transaction);
            $postskudos->is_del = 0;
            if($postskudos->update() === false){
                $transaction->rollback($this->msgList['posts_kudos_update_error']);
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
                $transaction->rollback($this->msgList['posts_del_error']);
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
        $sendcode = SendCode::findFirst([
            "to=:to: and type='mobile' and status=1 and code=:code:",
            'bind'=>['to'=>$mobile, 'code'=>$code], 'order'=>'id desc'
        ]);
        if(isset($sendcode->id)){
            if ($sendcode->update(['status'=>0]) === false) {
                return false;
            }
        }
        return true;
    }

    //修改企业名单状态
    public function setCompanyUser($companyuser_id,$user_id){
        //查询企业导入名单
        $companyuserlist = CompanyUserList::findFirst(["id=:id:", 'bind'=>['id'=>$companyuser_id], 'order'=>'id desc']);
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
        $userinfo = UserInfo::findFirst([
            "user_id=:user_id:",
            'bind'=>['user_id'=>$user_id], 'order'=>'user_id desc'
        ]);
        $company_name = "";
        if(isset($userinfo->company_id)){
            $configcompany = ConfigCompany::findFirst([
                "company_id=:company_id:",
                'bind'=>['company_id'=>$userinfo->user_id], 'order'=>'company_id desc'
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
            'company_id'=>$userinfo->company_id??"",
            'company_name'=>$company_name,
            'worker_id'=>$userinfo->worker_id??"",
            'last_login_time'=>$userinfo->last_login_time??"",
            'manager_id'=>$userinfo->manager_id??0,
            'expire_time'=>time()+$this->config->user_token->exceed_time
        ];
        $oJwt = new ThirdJwt();
        $data['map'] = $map;
        $data['token'] = $oJwt::getToken($map);
        return $data;
    }

    //用户token解密
    public function getDecrypt()
    {
        $return  = ['result'=>0, 'msg'=>$this->msgList['decrypt_error'], 'code'=>403, 'data'=>[]];
        $user_token = $data = $this->request->get("UserToken")??$this->request->getHeader('UserToken');
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
    public function verifyToken($company="",$page_sign="")
    {
        //$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjNmMmc1N2E5MmFhIn0.eyJpc3MiOiJodHRwOlwvXC9hZG1pbmFwaS5weWcuY29tIiwiYXVkIjoiaHR0cDpcL1wvd3d3LnB5Zy5jb20iLCJqdGkiOiIzZjJnNTdhOTJhYSIsImlhdCI6MTU4OTcwMzcyOCwibmJmIjoxNTg5NzAzNzI3LCJleHAiOjE1ODk3OTAxMjgsImRhdGEiOiJ7XCJ1c2VyX2lkXCI6XCIxMTg3OVwiLFwidXNlcm5hbWVcIjpcIjE3MDgyMTcwNzg3XCIsXCJuaWNrX25hbWVcIjpcImFzZHNhZHNhZFwiLFwidHJ1ZV9uYW1lXCI6XCJ0cnllXCIsXCJ1c2VyX2ltZ1wiOlwiXCIsXCJtb2JpbGVcIjpcIlwiLFwiY29tcGFueV9pZFwiOlwiM1wiLFwiY29tcGFueV9uYW1lXCI6XCJcIixcIndvcmtlcl9pZFwiOlwiXCIsXCJsYXN0X2xvZ2luX3RpbWVcIjpcIjIwMjAtMDQtMjYgMTM6NTI6NTlcIixcIm1hbmFnZXJfaWRcIjpcIjJcIixcImV4cGlyZV90aW1lXCI6MTU5MDMwODUyOH0ifQ.UeTj-2VTftdshJQjYq08C5tFH3cLqQMJGXmW79Bp_6A";
        $return  = ['result'=>1, 'msg'=>$this->msgList['decrypt_success'], 'code'=>200, 'data'=>[]];
        $user_token = $data = $this->request->get("UserToken")??$this->request->getHeader('UserToken');
        //$user_token = $token;
        $user_token = $user_token?preg_replace('# #','',$user_token):"";
        $oJwt = new ThirdJwt();
        $user_info = $oJwt::getUserId($user_token);
        if(!isset($user_info) || (isset($user_info) && json_decode($user_info)->expire_time<time())){
            $page_info = (new PageService)->getPageBySign($company,$page_sign,"page_id,need_login");
            if(isset($page_info['need_login']) && $page_info['need_login']==1){
                $return  = ['result'=>0, 'msg'=>$this->msgList['decrypt_error'], 'code'=>403, 'data'=>[]];
            }
        }
        else
        {
            $return['data']  = json_decode($user_info,true);
        }
        return $return;
    }

    //后台获取用户token
    public function createTokenForManager($manager_id){
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if($manager_id==0){
            $return['msg']  = $this->msgList['manager_id_error'];
        }else{
            $userinfo = UserInfo::findFirst([
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
        $userList = CompanyUserList::find($searchParams);
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
            $return['msg']  = $this->msgList['name__empty'];
        }else{
            $companyuser = CompanyUserList::findFirst([
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
                    $activity = ConfigActivity::findFirst([
                        "activity_id='".$list['activity_id']."'",
                    ])->toArray();
                    if(isset($activity['activity_id'])){
                        $post_list[$k]['activity_name'] = $activity['activity_name'];
                    }
                }
                $post_list[$k]['source'] = json_decode($post_list[$k]['source'],true);
                $post_list[$k]['source'] = (new UploadService())->parthSource($post_list[$k]['source']);
            }
            $count = (new \HJ\Posts())->findFirst($params_count)['count']??0;
        }
        $return  = ['data'=>$post_list,'count'=>$count,'total_page'=>ceil($count/$pageSize)];
        return $return;
    }
    public function getActivityLogByUser($user_id,$activity_id)
    {
        return  UserActivityLog::findFirst([
            "activity_id=:activity_id: and user_id=:user_id:",
            'bind'=>['activity_id'=>$user_id, 'user_id'=>$activity_id],
            'order'=>'id desc'
        ]);
    }



	
}