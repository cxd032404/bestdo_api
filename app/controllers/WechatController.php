<?php

use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Phalcon\Mvc\Controller;

class WechatController extends BaseController
{


    /*获取用户信息并判断是否关注*/
    public function indexAction()
    {
        $appid = $this->key_config->aliyun->wechat->appid;
        $appsecret = $this->key_config->aliyun->wechat->appsecret;

        echo "----------------------打印公众号appid和appsecret--------------------------";
        var_dump($appid);
        var_dump($appsecret);
        if (empty($_REQUEST["code"])) {//第二步：获取code
            echo "----------------------调取接口获取code--------------------------";
            $company_id = $_REQUEST['company_id']??"1";
            $redirect_url = 'http://api.staffhome.cn/Wechat/index';
            $this->getCode($appid,$redirect_url,$company_id);
            return;
        }else{
            echo "----------------------打印获取的code--------------------------";
            //第三步：获取网页授权access_token和openid
            $code = $_REQUEST['code']??"";
            $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
            echo "----------------------打印网页授权access_token和openid--------------------------";
            var_dump($oauth2);
            $openid = $oauth2['openid'];
            //测试一：根据网页授权access_token和openid获取用户信息
            $oauth_userinfo = $this->getOauthUserInfo($oauth2['access_token'],$openid);
            echo "----------------------打印网页授权access_token获取的用户信息--------------------------";
            var_dump($oauth_userinfo);
            if (array_key_exists('errcode', $oauth_userinfo) && $oauth_userinfo['errcode'] != '0') {
                //return json_encode($oauth_userinfo);
            }
        }
        //第一步：根据appid和appsecret获取全局access_token
        $access_token = $this->getAccessToken($appid,$appsecret);
        echo "----------------------打印全局access_token--------------------------";
        var_dump($access_token);
        echo 222222222333;
        echo "----------------------打印全局access_token获取的用户信息的参数--------------------------";
        var_dump($openid);
        var_dump($access_token);
        //第四步：根据全局access_token和openid获取用户信息
        $userinfo = $this->getUserInfo($access_token,$openid);
        echo "----------------------打印全局access_token获取的用户信息--------------------------";
        var_dump($userinfo);
        var_dump($userinfo['subscribe']);
        if (array_key_exists('errcode', $userinfo) && $userinfo['errcode'] != '0') {
            //return json_encode($userinfo);
            echo '获取用户信息错误';
        }
        if($userinfo['subscribe']==1){
            echo '已关注';
        }else{
            echo '未关注';
        }
        exit();
    }

    //获取网页授权code
    public function getCode($appid,$redirect_url="",$company_id=0)
    {
        $url_get ="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_userinfo&state=".intval($company_id)."#wechat_redirect";
        header("Location:" . $url_get);
    }

    //获取网页授权access_token
    public function getOauthAccessToken($appid,$appsecret,$code)
    {
        print_r($this->getRedis("oauth_access_token"));
        $oauth_access_token_redis = $this->getRedis("oauth_access_token");
        echo "----------------------打印网页授权access_token的redis存储信息--------------------------";
        var_dump($oauth_access_token_redis);
        if( $oauth_access_token_redis && $oauth_access_token_redis["oauth_expires_time"] && $oauth_access_token_redis["oauth_expires_time"]>time() ){
            $oauth_access_token = $oauth_access_token_redis;
        }
        else{
            $url_get = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
            echo "----------------------打印全局access_token的url请求信息--------------------------";
            var_dump($url_get);
            $oauth_access_token = $this->getJson($url_get);
            if(!array_key_exists('errcode', $oauth_access_token)){
                //用户token存入redis缓存中
                $oauth_access_token['oauth_expires_time'] = time()+intval($oauth_access_token['expires_in']);
                $this->setRedis('oauth_access_token',$oauth_access_token);
            }
        }
        return $oauth_access_token;
    }

    //获取全局access_token
    public function getAccessToken($appid,$appsecret)
    {
        $access_token_redis = $this->getRedis("access_token");
        echo "----------------------打印全局access_token的redis存储信息--------------------------";
        var_dump($access_token_redis);
        if( $access_token_redis && $access_token_redis["expires_time"] && $access_token_redis["expires_time"]>time() ){
            $access_token = $access_token_redis;
        }
        else{
            $url_get = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret."";
            echo "----------------------打印全局access_token的url请求信息--------------------------";
            var_dump($url_get);
            $access_token = $this->getJson($url_get);
            var_dump($access_token);
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
        echo "----------------------打印全局access_token获取用户信息的url请求信息--------------------------";
        var_dump($url_get);
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //利用网页授权access_token获取用户信息
    public function getOauthUserInfo($oauth_access_token,$openid)
    {
        $url_get = "https://api.weixin.qq.com/sns/userinfo?access_token=".$oauth_access_token."&openid=".$openid."&lang=zh_CN";
        echo "----------------------打印网页授权access_token获取用户信息的url请求信息--------------------------";
        var_dump($url_get);
        $userinfo = $this->getJson($url_get);
        return $userinfo;
    }

    //CURL
    function getJson($url){
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