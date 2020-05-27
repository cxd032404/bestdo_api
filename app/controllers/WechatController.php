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
        echo "----------------------打印公众号id和秘钥--------------------------";
        var_dump($appid);
        var_dump($appsecret);
        //第一步：根据appid和appsecret获取全局access_token
        $access_token = $this->getAccessToken($appid,$appsecret);
        echo "----------------------打印全局access_token--------------------------";
        print_r($access_token);
        //日志记录
        $this->logger->info(json_encode($access_token));

        if (empty($_REQUEST["openid"])){
            //未获得用户openid，判断是否关注
            if (empty($_REQUEST["code"])) {//第二步：获取code
                $company_id = $_REQUEST['company_id']??"";
                $redirect_url = 'http://www.staffhome.cn/api/Wechat/index';
                $this->getCode($appid,$redirect_url,$company_id);
                return;
            }else{
                echo "----------------------打印code--------------------------";
                print_r($_REQUEST['code']);
                //第三步：获取网页授权access_token和openid
                $code = $_REQUEST['code']??"";
                $oauth2 = $this->getOauthAccessToken($appid,$appsecret,$code);
                $openid = $oauth2['openid'];
                echo "----------------------打印网页授权access_token和openid--------------------------";
                print_r($oauth2);
                //日志记录
                $this->logger->info(json_encode($oauth2));
            }
        }else{
            //已获得openid，判断是否关注
            $openid = $_REQUEST["openid"]??"";
        }
        //第四步：根据全局access_token和openid获取用户信息
        $userinfo = $this->getUserInfo($access_token,$openid);

        echo "----------------------打印用户信息--------------------------";
        print_r($userinfo);
        //日志记录
        $this->logger->info(json_encode($userinfo));

        if (array_key_exists('errcode', $userinfo) && $userinfo['errcode'] != '0') {
            return json_encode($userinfo);
        }
        var_dump($userinfo);
        if($userinfo['subscribe']==1){
            echo '已关注';
        }else{
            echo '未关注';
        }
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
        if( $this->redis->get("oauth_access_token") && $this->redis->get("oauth_expires_in") && $this->redis->get("oauth_expires_in")>time() ){
            $oauth_access_token['access_token'] = $this->redis->get("oauth_access_token");
            $oauth_access_token['openid'] = $this->redis->get("oauth_openid");
        }else{
            $url_get = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=$code&grant_type=authorization_code";
            $oauth_access_token = $this->getJson($url_get);
            if(!array_key_exists('errcode', $oauth_access_token)){
                //用户token存入redis缓存中
                $this->redis->set('oauth_access_token',$oauth_access_token['access_token']);
                $this->redis->expire('oauth_access_token',$this->config->user_token->exceed_time);
                $this->redis->set('oauth_expires_in',time()+intval($oauth_access_token['expires_in']));
                $this->redis->expire('oauth_expires_in',$this->config->user_token->exceed_time);
                $this->redis->set('oauth_openid',$oauth_access_token['openid']);
                $this->redis->expire('oauth_openid',$this->config->user_token->exceed_time);
            }
        }
        return $oauth_access_token;
    }

    //获取全局access_token
    public function getAccessToken($appid,$appsecret)
    {
        if( $this->redis->get("access_token") && $this->redis->get("expires_in") && $this->redis->get("expires_in")>time() ){
            $access_token['access_token'] = $this->redis->get("access_token");
        }else{
            $url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret;
            $access_token = $this->getJson($url_get);
            if(!array_key_exists('errcode', $access_token)){
                //用户token存入redis缓存中
                $this->redis->set('access_token',$access_token['access_token']);
                $this->redis->expire('access_token',$this->config->user_token->exceed_time);
                $this->redis->set('expires_in',time()+intval($access_token['expires_in']));
                $this->redis->expire('expires_in',$this->config->user_token->exceed_time);
            }
        }
        return $access_token['access_token'];
    }

    //获取用户信息
    public function getUserInfo($access_token,$openid)
    {
        $url_get = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid;
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


}