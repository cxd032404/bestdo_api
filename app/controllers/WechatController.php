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
     * 小程序code登录
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
        return $this->success($openId);
    }
    /*
     * 小程序数据解码
     * 参数
     * session_key
     * data：密文
     * iv：偏移量
     * */
    public function wechatDecryptAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $this->logger->info(json_encode($data));
        $session_key = trim($data['session_key']??"");
        $iv = trim($data['iv']??"");
        $data = trim($data['data']??"");
        //解码
        $decrypt = (new WechatService)->decryptData($data,$iv,$this->key_config->wechat_mini_program,$session_key);
        return $this->success($decrypt);
    }
}