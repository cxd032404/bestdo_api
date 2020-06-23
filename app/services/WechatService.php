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

class WechatService extends BaseService
{


    private $templete = [
        'join'=>'申请加入',
        'pass'=>'通过了您的申请',
        'leave'=>'离开了',
        'reject'=>'拒绝了您的申请',
        'activity'=>'加入了'
    ];
    //俱乐部消息类型
    private  $clubTypeList = [
        'joinClub',
        'leaveClub',
        'applicationPass',
        'applicationReject'
    ];
    private $activityTypeList = [
        'joinActivity',
        'ActivityJoin',
    ];


    /*更新用户微信信息*/
    public function getOpenIdByCode($wechat = [],$code="")
    {
        $wechat_cache = $this->config->cache_setting->wechat;
        $redis_key = $wechat_cache->name.$code;
        $appid = $wechat['appid'];
        $cache = $this->redis->get($redis_key);
        if($cache!= "")
        {
            $oauth2 = json_decode($cache,true);
        }
        else
        {
            $appsecret = $wechat['appsecret'];
            //第二步：获取网页授权access_token和openid
            $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
        }
        //var_dump($oauth2);
        $openId = "";
        if (!array_key_exists('errcode', $oauth2)) {
            $openId = $oauth2['openid'];
        }
        if($openId != "")
        {
            //用户token存入redis缓存中
            $this->redis->set($redis_key,json_encode($oauth2));
            $this->redis->expire($redis_key,$wechat_cache->expire);//设置过期时间,不设置过去时间时，默认为永久保持
        }

        return $openId;
    }

    /*更新用户微信信息*/
    public function getWechatUserAction($wechat=[],$user_id=0,$code="")
    {
        $wechat_cache = $this->config->cache_setting->wechat;
        $redis_key = $wechat_cache->name.$code;
        $cache = $this->redis->get($redis_key);
        if($cache!= "")
        {
            $oauth2 = json_decode($cache,true);
        }
        else
        {
            $appid = $wechat['appid'];
            $appsecret = $wechat['appsecret'];
            //第二步：获取网页授权access_token和openid
            $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
        }
        //var_dump($oauth2);
        if (!array_key_exists('errcode', $oauth2)) {
            $openid = $oauth2['openid'];
        }
        //第三步：根据网页授权access_token和openid获取用户信息（不包含是否关注）
        $oauth_userinfo = $this->getOauthUserInfo($oauth2['access_token'],$openid);
        //var_dump($oauth_userinfo);
        if (!array_key_exists('errcode', $oauth_userinfo)) {
            //修改用户信息
            $userinfo = \HJ\UserInfo::findFirst(["user_id = '".$user_id."' and is_del=0"]);
            //var_dump($userinfo);
            if($userinfo){
                $userinfo->wechatid = $oauth_userinfo['openid'];
                $userinfo->unionid = $oauth_userinfo['unionid']??"";
                $userinfo->nick_name = $oauth_userinfo['nickname'];
                $userinfo->sex = $oauth_userinfo['sex'];
                $userinfo->user_img = $oauth_userinfo['headimgurl'];
                $userinfo->wechatinfo = json_encode($oauth_userinfo);
                $userinfo->update();
            }
        }
        return true;
    }


    /*测试----获取用户信息并判断是否关注*/
    public function indexAction()
    {
        echo '测试信息';die;
        $appid = $this->key_config->wechat->appid;
        $appsecret = $this->key_config->wechat->appsecret;
        if (empty($_REQUEST["code"])) {//第一步：获取微信授权code
            $company_id = $_REQUEST['company_id']??"1";
            $redirect_url = 'http://api.staffhome.cn/Wechat/index';
            $this->getCode($appid,$redirect_url,$company_id);
            return;
        }else{
            //第二步：获取网页授权access_token和openid
            $code = $_REQUEST['code']??"";
            $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
            if (array_key_exists('errcode', $oauth2) && $oauth2['errcode'] != '0') {
                return $this->failure($oauth2);
            }
            $openid = $oauth2['openid'];
            //第三步：根据网页授权access_token和openid获取用户信息（不包含是否关注）
            $oauth_userinfo = $this->getOauthUserInfo($oauth2['access_token'],$openid);
            if (array_key_exists('errcode', $oauth_userinfo) && $oauth_userinfo['errcode'] != '0') {
                return $this->failure($oauth_userinfo);
            }
        }
        //第四步：根据appid和appsecret获取全局access_token
        $access_token = $this->getAccessToken($appid,$appsecret);
        //第五步：根据全局access_token和openid获取用户信息
        $userinfo = $this->getUserInfo($access_token,$openid);
        if (array_key_exists('errcode', $userinfo) && $userinfo['errcode'] != '0') {
            return $this->failure($userinfo);
        }
        if($userinfo['subscribe']==1){
            echo '已关注';
        }else{
            echo '未关注';
        }
        $return  = ['result'=>1, 'msg'=>"", 'code'=>200, 'data'=>['access_token_user_info'=>$userinfo, 'oauth_access_token_user_info'=>$oauth_userinfo]];
        return $this->success($return);
    }
    /*测试----获取用户信息并判断是否关注*/
    public function getCodeForManager()
    {
        $appid = $this->key_config->wechat->appid;
        $appsecret = $this->key_config->wechat->appsecret;
        if (empty($_REQUEST["code"])) {//第一步：获取微信授权code
            $redirect_url = $this->request->getServerName().$this->request->getURI();
            $redirect_url = str_replace("api.staffhome.cn","http://www.staffhome.cn/api",$redirect_url);
            $this->getCode($appid,$redirect_url,0);
            return;
        }else{
            //第二步：获取网页授权access_token和openid
            $code = $_REQUEST['code']??"";
            $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
            if (array_key_exists('errcode', $oauth2) && $oauth2['errcode'] != '0') {
                return $this->failure($oauth2);
            }
            $openid = $oauth2['openid'];
            $redirect_url = $_REQUEST["redirect"]."&openid=".$openid;
            header("Location:" . $redirect_url);
        }
    }


    //查询是否在微信浏览器打开
    public function is_weixin(){
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }

    //获取网页授权code
    public function getCode($appid="",$redirect_url="",$user_id=0)
    {
        $url_get ="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=".intval($user_id)."#wechat_redirect";
        header("Location:" . $url_get);
    }

    //获取网页授权access_token
    public function getOauthAccessToken($appid="",$appsecret="",$code="")
    {
        $url_get = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
        $oauth_access_token = $this->getJson($url_get);
        return $oauth_access_token;
    }

    //获取全局access_token
    public function getAccessToken($appid="",$appsecret="")
    {
        $access_token_redis = $this->getRedis("access_token");
        if( $access_token_redis && $access_token_redis["expires_time"] && $access_token_redis["expires_time"]>time() ){
            $access_token = $access_token_redis;
        }
        else{
            $url_get = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret."";
            $access_token = $this->getJson($url_get);
            if(!array_key_exists('errcode', $access_token)){
                //用户token存入redis缓存中
                $access_token['expires_time'] = time()+intval($access_token['expires_in']);
                $this->setRedis('access_token',$access_token);
            }
        }
        return $access_token['access_token'];
    }

    //利用全局access_token和openid获取用户信息
    public function getUserInfo($access_token="",$openid="")
    {
        $url_get = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."";
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //利用网页授权access_token获取用户信息
    public function getOauthUserInfo($oauth_access_token="",$openid="")
    {
        $url_get = "https://api.weixin.qq.com/sns/userinfo?access_token=".$oauth_access_token."&openid=".$openid."&lang=zh_CN";
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //获取分享所需参数
    public function getSignPackage($appid="",$appsecret="",$url="")
    {
        $AccessToken = $this->getAccessToken($appid,$appsecret);
        $jsapiTicket = $this->_newGetApiTicket($AccessToken);
        // 注意 URL 一定要动态获取，不能 hardcode.
        //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        //$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $appid,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;

    }

    //获取授权页ticket
    public function _newGetApiTicket($access_token="")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$access_token";
        $res = $this->httpGet($url);
        $ticket = $res['ticket'];
        return $ticket;
    }

    //获取随机16位字符串
    public function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //CURL
    public function getJson($url=""){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    private function httpGet($url="")
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return json_decode($res, true);
    }

    //获取redis数据
    public function getRedis($key){
        $value = $this->redis->get($key);
        $value_serl = @unserialize($value);
        if(is_object($value_serl)||is_array($value_serl)){
            return $value_serl;
        }
        return $value;
    }

    //存储redis数据
    public function setRedis($key,$value){
        if(is_object($value)||is_array($value)){
            $value = serialize($value);
        }
        return $this->redis->set($key,$value);
    }

    //删除redis数据
    public function delRedis($key){
        $value = $this->redis->del($key);
        return $value;
    }


    /*
     * 检测accessToken
     */
    public function checkWechatAccessToken(){
        $redisSettings = $this->config->cache_settings->accessToken;
        $accessToken = $this->redis->get($redisSettings->name);
        if($accessToken)
       {
            return $accessToken;
       }else
        {
            return $this->getWechatAccessToken();
        }
    }
    /*
     * 获取最新accessToken
     */
    public function getWechatAccessToken(){
        $redisSettings = $this->config->cache_settings->accessToken;
        $appid = $this->key_config->wechat->appid??"";
        $appsecret = $this->key_config->wechat->appsecret??"";
        $AccessTokenUri =  "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $data = file_get_contents($AccessTokenUri);
        $accessTokenInfo = json_decode($data,true);
        $this->redis->set($redisSettings->name,$accessTokenInfo['access_token']);
        $this->redis->expire($redisSettings->name,$accessTokenInfo['expires_in']);
        return $accessTokenInfo['access_token'];
    }
    /*
     * 推送微信公众号信息
     */
    public function sendWechatMessage($accessToken,$touser_openid,$template_id,$content)
    {
       $sendUrl = "http://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";
        $sendData = [
           'touser'=>$touser_openid, //接收方
           'template_id'=>$template_id,
            'appid'=>$this->key_config->wechat->appid,
            'data'=>[
                "cardNumber"=>[
                     'value'=>$content,
                     'color'=>'#173177'
                ],
                "type"=>[
                    'value'=>$content,
                    'color'=>'#173177'
                ],
                "VIPName"=>[
                    'value'=>$content,
                    'color'=>'#173177'
                ],
                "VIPPhone"=>[
                    'value'=>$content,
                    'color'=>'#173177'
                ],
                "expDate"=>[
                    'value'=>$content,
                    'color'=>'#173177'
                ]
            ]

       ];
       $res = $this->curl->post_request($sendUrl,$sendData);
       if($res['errcode']==0)
           return true;
           return false;

    }
    /*
     *发送微信公众号消息接口
     */
    public function  sendMessage($info,$type)
    {
        $userListSend = $this->generateUserListSend($info,$type);
        if(!$userListSend['result'])
        {
            return $userListSend;
        }
        $contentSend = $this->generateContentSend($info,$type);
        if(!$contentSend['result'])
        {
            return $contentSend;
        }
        return $this->send($userListSend['user_list_info'],$contentSend['content']);
    }

    /*info  包含club_id user_id
     * user_id始终是被操作方user_id
     * 获取接收人信息
     */
    public function generateUserListSend($info,$type)
    {

        if(!isset($info['user_id'])||!$info['user_id'])
        {
            return ['result'=>0,'msg'=>'user_id错误'];
        }
        if(in_array($type,$this->clubTypeList))
        {
            if(!isset($info['club_id'])||!$info['club_id'])
            {
                return ['result'=>0,'msg'=>'club_id错误'];
            }
        }
        //活动签到类型
        else if($type == 'joinActivity' || $type == 'checkin')
        {
            if(!isset($info['activity_id'])||!$info['activity_id'])
            {
                return ['result'=>0,'msg'=>'activity_id错误'];
            }
        }else
        {
            return ['result'=>0,'msg'=>'未知类型'];
        }

        $user_list_info= [];
        switch ($type)
        {
            case 'join' :
            case 'leave':$club_id = $info['club_id'];
                                 $user_list = (new ClubService())->getClubManagerList($club_id);break;
            case 'pass':
            case 'reject':$user_list[] = $info['user_id'];break;
            case 'activity':$create_user_id = (new \HJ\Activity())->findFirst(['activity_id ='.$info['activity_id'],'columns'=>'create_user_id']);
                          $user_list[] = $create_user_id->create_user_id;break;
            default:return ['result'=>0,'msg'=>'未知类型'];
        }


        return ['result'=>1,'msg'=>'','user_list_info'=>$user_list_info];

    }

    /*
     * 获取模板
     */
    public function generateContentSend($info,$type){
        if(!isset($info['user_id'])||!$info['user_id'])
        {
            return ['result'=>0,'msg'=>'operate_id错误'];
        }
        $user_id = $info['user_id'];
        $user_info = (new UserService())->getUserInfo($user_id,'true_name,nick_name');
        if(!$user_info->true_name)
        {
            $user_name = $user_info->nick_name;
        }else
        {
            $user_name = $user_info->true_name;
        }
        //俱乐部类型
        if($type == 'join'|| $type =='pass' || $type =='reject' || $type == 'leave')
        {
            if(!isset($info['club_id'])||!$info['club_id'])
            {
                return ['result'=>0,'msg'=>'club_id错误'];
            }
            $club_info = (new ClubService())->getClubInfo($info['club_id'],'club_id,club_name');
            $club_name = $club_info->club_name;
        }
        //活动签到类型
        else if($type == 'activity' || $type == 'checkin')
        {
            if(!isset($info['activity_id'])||!$info['activity_id'])
            {
                return ['result'=>0,'msg'=>'activity_id错误'];
            }
            //获取创建者user_id
            $activity_info = (new ActivityService())->getActivityInfo($info['activity_id'],'activity_id,activity_name');
            $activity_name = $activity_info->activity_name;
        }else
        {
            return ['result'=>0,'msg'=>'未知类型'];
        }

        $content = '';

        switch ($type){
            case 'join':$content = $user_name.$this->templete['join'].$club_name;break;//史说政申请加入足球俱乐部
            case 'pass': $content = $club_name.$this->templete['pass']; break; //足球俱乐部通过了你的申请
            case 'reject':$content = $club_name.$this->templete['reject'];break;//足球俱乐部拒绝了你的申请
            case 'leave':$content = $user_name.$this->templete['leave'].$club_name;break;
            case 'activity':$content = $user_name.$this->templete['activity'].$activity_name;
            defalt:  return ['result'=>0,'msg'=>'类型有误'];
        }
        return ['result'=>1,'msg'=>'','content'=>$content];



        }


     //消息存入redis队列
     public function send($userList,$contentSend)
     {
         $redisKey = $this->config->redisQueue->wechatMessageQueue;
         foreach ($userList as $key=>$value)
         {
             $message = [];
             $message['user_id'] = $value['user_id'];
             $message['openid'] = $value['openid'];
             $message['content'] = $contentSend;
             print_r($message);die();
             $res = $this->redis->rpush($redisKey,json_encode($message));
             if(!$res)
             {
                 $error_data[] = $message;
             }
         }
         if(!isset($error_data))
         {
             return ['result'=>1,'msg'=>'发送成功'];
         }else
         {
             return ['result'=>0,'msg'=>'发送失败','error_data'=>$error_data];
         }

     }



}