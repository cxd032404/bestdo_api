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
        $appid = $this->key_config->aliyun->wechat->appid??"";
        $appsecret = $this->key_config->aliyun->wechat->appsecret??"";
        $return  = (new WechatService)->getSignPackage($appid,$appsecret);
        return $this->success($return);
    }



}