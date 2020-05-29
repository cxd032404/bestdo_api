<?php

use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Phalcon\Mvc\Controller;

class WechatController extends BaseController
{

    /*更新用户微信信息*/
    public function getWechatUserAction($user_id=0)
    {
        $appid = $this->key_config->aliyun->wechat->appid;
        $appsecret = $this->key_config->aliyun->wechat->appsecret;
        //判断是否在微信浏览器打开，不在微信浏览器打开无法获取code
        if($this->is_weixin()){
            if (empty($_REQUEST["code"])) {//第一步：获取微信授权code
                $redirect_url = 'http://api.staffhome.cn/Wechat/getWechatUser';
                $this->getCode($appid,$redirect_url,$user_id);
                return;
            }else{
                var_dump($_REQUEST['code']);
                //第二步：获取网页授权access_token和openid
                $code = $_REQUEST['code']??"";
                $user_id = $_REQUEST['state']??0;
                $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
                var_dump($oauth2);
                if (!array_key_exists('errcode', $oauth2)) {
                    $openid = $oauth2['openid'];
                    //第三步：根据网页授权access_token和openid获取用户信息（不包含是否关注）
                    $oauth_userinfo = $this->getOauthUserInfo($oauth2['access_token'],$openid);
                    var_dump($oauth_userinfo);
                    if (!array_key_exists('errcode', $oauth_userinfo)) {
                        //修改用户信息
                        $userinfo = UserInfo::findFirst(["user_id = '".$user_id."' and is_del=0"]);
                        var_dump($userinfo);
                        if($userinfo){
                            $userinfo->wechatid = $oauth_userinfo['openid'];
                            $userinfo->nick_name = $oauth_userinfo['nickname'];
                            $userinfo->sex = $oauth_userinfo['sex'];
                            $userinfo->user_img = $oauth_userinfo['headimgurl'];
                            $userinfo->wechatinfo = json_encode($oauth_userinfo);
                            $userinfo->update();
                        }
                    }
                }
            }
        }
    }


    /*测试----获取用户信息并判断是否关注*/
    public function indexAction()
    {
        $appid = $this->key_config->aliyun->wechat->appid;
        $appsecret = $this->key_config->aliyun->wechat->appsecret;
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

    //查询是否在微信浏览器打开
    public function is_weixin(){
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }

    //获取网页授权code
    public function getCode($appid,$redirect_url="",$user_id=0)
    {
        $url_get ="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=".intval($user_id)."#wechat_redirect";
        header("Location:" . $url_get);
    }

    //获取网页授权access_token
    public function getOauthAccessToken($appid,$appsecret,$code)
    {
        $url_get = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
        $oauth_access_token = $this->getJson($url_get);
        return $oauth_access_token;
    }

    //获取全局access_token
    public function getAccessToken($appid,$appsecret)
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
    public function getUserInfo($access_token,$openid)
    {
        $url_get = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."";
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //利用网页授权access_token获取用户信息
    public function getOauthUserInfo($oauth_access_token,$openid)
    {
        $url_get = "https://api.weixin.qq.com/sns/userinfo?access_token=".$oauth_access_token."&openid=".$openid."&lang=zh_CN";
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //CURL
    public function getJson($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
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


}