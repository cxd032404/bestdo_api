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
     * 小程序通过code获取sessionKey
     * 参数
     * code
     * （必填）：微信授权code
     * */
    public function miniProgramLoginAction()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $code = (isset($data['code']) && !empty($data['code']) && $data['code']!=='undefined' )?preg_replace('# #','',$data['code']):"";
        //通过code获取sessionKey,openid,Unionid
        $wechatUserInfo = (new WechatService)->getUserInfoByCode_mini_program($this->key_config->wechat_mini_program,$code);
        if($wechatUserInfo['openid'])
        {
            $return  = (new UserService)->miniProgramLogin($wechatUserInfo['unionid']??"",$wechatUserInfo['openid']??"");
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
        //通过code获取sessionKey,openid,Unionid
        $wechatUserInfo = (new WechatService)->getUserInfoByCode_mini_program($this->key_config->wechat_mini_program,$code);
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
        //解码
        $decrypt = (new WechatService)->decryptData($data,$iv,$this->key_config->wechat_mini_program,$code);
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
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($echoStr) {
            if ($this->checkSignature()) {
                echo $echoStr;
                exit;
            }
        }else
        {
            $this->responseMsg();
        }
    }
    public function responseMsg()
    {
        $postArr = file_get_contents('php://input', 'r');
        //获取到xml数据后，处理消息类型，并设置回复消息内容(回复就是直接打印xml数据)
        //数据格式
        $arr = $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if(strtolower($arr->MsgType)=="event")
        {
            $toUser = $arr->ToUserName;
            $foUser = $arr->FromUserName;
            $msgType = 'text';
            $createTime = time();
            $content = "感谢关注\"文体之窗\"。\n我们将竭诚为您服务，为您的企业丰富线上生活，提供员工舞台，管理健康大数据。";
            if(strtolower($arr->Event)=="subscribe")
            {//订阅
                $temp = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
                $temp = sprintf($temp,$foUser,$toUser,$createTime,$msgType,$content);
                echo $temp;
            }
        }
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

}