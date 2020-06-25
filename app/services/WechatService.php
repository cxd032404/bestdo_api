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

class WechatService extends BaseService
{


    /*更新用户微信信息*/
    public function getOpenIdByCode($wechat = [],$code="")
    {
        $wechat_cache = $this->config->cache_settings->wechat;
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
    public function updateUserWithWechat($wechat=[],$user_id=0,$code="")
    {
        $wechat_cache = $this->config->cache_settings->wechat;
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
    /*更新用户微信信息*/
    public function updateUserWithMiniProgram($user_id=0,$miniProgramUserInfo="")
    {
        $miniProgramUserInfo = json_decode($miniProgramUserInfo,true);
        //修改用户信息
        $userinfo = \HJ\UserInfo::findFirst(["user_id = '".$user_id."' and is_del=0"]);
        //var_dump($userinfo);
        if($userinfo){
            $userinfo->wechatid = $miniProgramUserInfo['openid']??"";
            $userinfo->unionid = $miniProgramUserInfo['unionid']??"";
            $userinfo->nick_name = $miniProgramUserInfo['nickname']??"";
            $userinfo->sex = $miniProgramUserInfo['sex'];
            $userinfo->user_img = $miniProgramUserInfo['headimgurl'];
            $userinfo->wechatinfo = json_encode($miniProgramUserInfo);
            $userinfo->update();
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





    //根据code获取小程序的用户身份信息
    public function getUserInfoByCode_mini_program($wechat = [],$code="")
    {
        $wechat_cache = $this->config->cache_settings->mini_program_code;
        $redis_key = $wechat_cache->name.$code;
        $cache = $this->redis->get($redis_key);
        if($cache!= "")
        {
            $user_info = json_decode($cache,true);
            if(isset($user_info['unionid']))
            {
            }
            else
            {
                $url_get = "https://api.weixin.qq.com/sns/jscode2session?appid=".$wechat['appid']."&secret=".$wechat['appsecret']."&js_code=".$code."&grant_type=authorization_code";
                $user_info = $this->getJson($url_get);
                if(isset($user_info['unionid']))
                {
                    //用户token存入redis缓存中
                    $this->redis->set($redis_key,json_encode($user_info));
                    $this->redis->expire($redis_key,$wechat_cache->expire);//设置过期时间,不设置过去时间时，默认为永久保持
                }
            }
        }
        else
        {
            $url_get = "https://api.weixin.qq.com/sns/jscode2session?appid=".$wechat['appid']."&secret=".$wechat['appsecret']."&js_code=".$code."&grant_type=authorization_code";
            $user_info = $this->getJson($url_get);
            if(isset($user_info['unionid']))
            {
                //用户token存入redis缓存中
                $this->redis->set($redis_key,json_encode($user_info));
                $this->redis->expire($redis_key,$wechat_cache->expire);//设置过期时间,不设置过去时间时，默认为永久保持
            }
        }
        return $user_info;
    }
    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData( $encryptedData, $iv, $wechat, $sessionKey )
    {
        $decryptClass = new WXBizDataCrypt($wechat['appid'],$sessionKey);
        $errCode = $decryptClass->decryptData($encryptedData, $iv, $data );
        if ($errCode == 0)
        {
            return ["result"=>1,"data"=>$data,"code"=>200];
        }
        else
        {
            return ["result"=>1,"msg"=>$errCode,"code"=>400];
        }

    }

}