<?php

use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Phalcon\Mvc\Controller;

class WechatController extends BaseController
{

    /*获取微信分享所需微信信息*/
    public function getSignPackageAction(){
        //接收参数并格式化
        $data = $this->request->get();
        $url = isset($data['url'])?$data['url']:"";
        if(!preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)){
            return $this->failure([],'请传输正确的url地址！',400);
        }
        $appid = $this->key_config->wechat->appid??"";
        $appsecret = $this->key_config->wechat->appsecret??"";
        $return  = (new WechatService)->getSignPackage($appid,$appsecret,$url);
        return $this->success($return);
    }

    public function bind4ManagerAction()
    {
        $data = $this->request->get();
        (new WechatService())->getCodeForManager();
        die();
    }

    /*
     * 微信公众号推送消息接口
     * touser 接收者openid
     * template_id 模板id
     * data 需要发送的信息
     */
    public function sendMessage($touser,$template_id,$data){
        $accessToken = (new WechatService())->checkWechatAccessToken();
        $result = (new WechatService())->sendWechatMessage($accessToken,$touser,$template_id,$data);
        return $result;
    }

    /*
     * 微信code登录
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function wechatMiniProgramLoginAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $this->logger->info(json_encode($data));
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        //echo "code:".$code;
        //调用手机号验证码登录方法
        $openId = (new WechatService)->getUserInfoByToken_mini_program($this->key_config->wechat_mini_program,$code);
        print_R($openId);
        die();
        //调用手机号验证码登录方法
        //$openId = 'oPCk01aWREJXeJK0IjOjDQfUWsmA';
        $return  = (new UserService)->wechatLogin($openId);
        //日志记录
        $this->logger->info(json_encode($openId));
        //返回值判断
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $cacheSetting = $this->config->cache_settings->user_token;
        $cacheName = $cacheSetting->name.$return['data']['user_info']['user_id'];
        $this->redis->set($cacheName,$return['data']['user_token']);
        $this->redis->expire($cacheName,$cacheSetting->expire);//设置过期时间,不设置过去时间时，默认为永久保持
        return $this->success($return['data']);
    }
}