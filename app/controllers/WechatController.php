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
        $session_key = trim($data['session_key']??"");
        $iv = trim($data['iv']??"");
        $data = trim($data['encryptedData']??"");
        //解码
        $decrypt = (new WechatService)->decryptData($data,$iv,$this->key_config->wechat_mini_program,$session_key);
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
     * 小程序code登录
     * 参数
     * session_key
     * encryptedData：密文
     * iv：偏移量
     * */
    public function miniProgramLoginAction_old()
    {
        //接收参数并格式化
        $data = $this->request->get();
        $session_key = trim($data['session_key']??"");
        $iv = trim($data['iv']??"");
        $data = trim($data['encryptedData']??"");
        //解码
        $decrypt = (new WechatService)->decryptData($data,$iv,$this->key_config->wechat_mini_program,$session_key);

        if($decrypt['unionId'])
        {
            $return  = (new UserService)->miniProgramLogin($decrypt['unionId']);
            if($return['result'])
            {
                return $this->success($return['data']);
            }
            else
            {
                $this->failure([],$decrypt['msg'],$decrypt['code']);
            }
        }
        else
        {
            return $this->failure([],"用户身份获取失败",403);
        }
    }
}