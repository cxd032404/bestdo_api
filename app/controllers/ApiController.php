<?php
// +----------------------------------------------------------------------
// | API控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     api.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;

class ApiController extends BaseController 
{

	public function testAction( $id = 0 ) 
	{
        $appid = $this->key_config->aliyun->wechat->appid??"";
        $appsecret = $this->key_config->aliyun->wechat->appsecret??"";
        $AccessToken = (new WechatService())->checkWechatAccessToken();
        print_r($AccessToken);
        die();
        $data = (new listService())->getListInfo(1,'list_type,list_id');
        print_r($data);die();
	    $return  = (new TestService)->test();
        //$return = $oService->test();
        $this->logger->info(json_encode($return));

        return $this->success($return);
    }

}
