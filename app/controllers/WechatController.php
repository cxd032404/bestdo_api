<?php

use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Phalcon\Mvc\Controller;

define("TOKEN", "hj202004");//此处的TOKEN就是接下来需要填在微信的配置里面的token，需要保持严格一致

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
        $app_id = $this->request->getHeader("Appid")??101;
        //$app_id = $this->key_config->wechat->appid??"";
        //$appsecret = $this->key_config->wechat->appsecret??"";
        $return  = (new WechatService)->getSignPackage($app_id,$url);
        return $this->success($return);
    }

    public function bind4ManagerAction()
    {
        $data = $this->request->get();
        (new WechatService())->getCodeForManager();
        die();
    }
    
    /*
     * 小程序code登录
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function miniProgramLoginAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        $app_id = $this->request->getHeader("Appid")??201;
        //通过code获取sessionKey,openid,Unionid
        $wechatUserInfo = (new WechatService)->getUserInfoByCode_mini_program($code,$app_id);
        if($wechatUserInfo['openid'])
        {
            $return  = (new LoginService())->miniProgramLogin($wechatUserInfo['unionid']??"",$wechatUserInfo['openid']??"",$app_id);
            if($return['result'])
            {
                return $this->success($return['data']);
            }
            else
            {
                $this->failure([],$return['msg'],$return['code']);
            }
        }
        else
        {
            return $this->failure([],"用户身份获取失败",403);
        }
    }
    /*
     * 小程序通过code获取sessionKey
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function getSessionKeyAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        $app_id = $this->request->getHeader("Appid")??101;
        //通过code获取sessionKey,openid,Unionid
        $wechatUserInfo = (new WechatService)->getUserInfoByCode_mini_program($code,$app_id);
        if($wechatUserInfo['openid'])
        {
            return $this->success($wechatUserInfo);
        }
        else
        {
            return $this->failure([],"用户身份获取失败",403);
        }
    }
    /*
     * 微信code登录
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function wechatCodeLoginAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        $app_id = $this->request->getHeader("Appid")??101;
        //调用手机号验证码登录方法
        $openId = (new WechatService)->getOpenIdByCode($code,$app_id);
        //调用手机号验证码登录方法
        $return  = (new LoginService())->wechatLogin($openId,$app_id);
        //返回值判断
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        return $this->success($return['data']);
    }
    /*
     * 小程序数据解码
     * 参数
     * session_key
     * encryptedData：密文
     * iv：偏移量
     * */
    public function wechatDecryptAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $code = trim($data['code']??"");
        $iv = trim($data['iv']??"");
        $data = trim($data['encryptedData']??"");
        $app_id = $this->request->getHeader("Appid")??201;
        //解码
        $decrypt = (new WechatService)->decryptData($data,$iv,$this->key_config->tencent,$code,$app_id);
        if($decrypt['result'])
        {
            $this->success($decrypt['data']??[],"",$decrypt['code']);
        }
        else
        {
            $this->failure([],$decrypt['msg'],$decrypt['code']);
        }
    }

    /*
     * 编辑公众号菜单
     */
    public function wechatMenueAction(){
        (new WechatService())->wechatMenue();

    }

    /*
     * 微信公众号验证
     */
    public function wechatAccountAction()
    {
        $echoStr = $_GET["echostr"]??'';
        //valid signature , option
        if($echoStr) {
            if ($this->checkSignature()) {
                echo $echoStr;
                exit;
            }
        }else
        {
                echo   $this->responseMsg();
                exit;
        }
    }
    public function responseMsg()
    {
        $postArr = file_get_contents('php://input', 'r');
        //获取到xml数据后，处理消息类型，并设置回复消息内容(回复就是直接打印xml数据)
        //数据格式
        $message =  simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if(!$message)
        {
            return 'success';
        }
        //事件回复
        $res = (new WechatMessageService())->answer($message);
        return $res;
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){

            return true;
        }else{

            return false;
        }
    }

    /*
     * 检测小程序文字内容
     */
    public function wechatMsgCheckAction(){
        $checkContent =  $_GET["checkMsg"]??'';
        $app_id = $this->request->getHeader("Appid")??101;
        $return = (new WechatService())->wechatMsgCheck($checkContent,$app_id);
        if($return['result'])
        {
            return $this->success();
        }else
        {
            $this->success($return['msg']);
        }
    }

    /*
     *生成小程序二维码
     */
    public function miniprogramQrcodeAction(){

        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;

        $company_id = $this->request->getPost('company_id')??1;

         //文件后缀  公司名加分享人id
        $suffix = 'company_id_'.$company_id.'user_id_'.$user_id;
        $suffix = md5($suffix);
        $file_name = '/runtime/codes/qrcode_'.$suffix.'.png';
        $file_path = ROOT_PATH.$file_name;

        if(file_exists($file_path))
         { //域名
             $host = $_SERVER['HTTP_HOST'];

             return $this->success($host.$file_name);
         }
         if(!is_dir(ROOT_PATH."/runtime/codes/"))
         {
             mkdir(ROOT_PATH."/runtime/codes/",0777,true);
         }
        $access_token = (new WechatService())->getAccessToken(202);
        $path="pages/shareb/shareb?company_d=$company_id&user_id=$user_id";
        $width='430';
        $post_data='{"path":"'.$path.'","width":'.$width.'}';
        $url="https://api.weixin.qq.com/wxa/getwxacode?access_token=$access_token";
        $res = (new WebCurl())->curl_post($url,$post_data,0);
         if($res)
         {
             file_put_contents($file_path,$res);
             $host = $_SERVER['HTTP_HOST'];

             return $this->success($host.$file_name);
         }else
         {
             return $this->error([],'生成失败');
         }
    }

}