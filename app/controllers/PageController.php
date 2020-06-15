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

class PageController extends BaseController
{

	public function getPageAction( $company ="",$page_sign = "")
	{
	    /*用户token验证开始*/
        //调用user_token解密方法
        $user_info  = (new UserService)->verifyTokenForPage($company,$page_sign);
        //返回值判断
        if($user_info['result']!=1){
            return $this->failure(['jump_url'=>'/login'],$user_info['msg'],$user_info['code']);
        }
        /*用户token验证结束*/
        $pageService = new PageService();
        $params = $this->request->get();
        $params = $params['params']??"";
        //$params = $txt;
        $paramsCheck = $pageService->checkPageParams($params,$company,$page_sign);
        if(!$paramsCheck['result'])
        {
            return $this->failure($paramsCheck['detail']??[],$paramsCheck['code']);
        }
        $return  = $pageService->getPageInfo($company,$page_sign,$params,$user_info);
        if($return['result'])
        {
            return $this->success($return['data']);
        }
        else
        {
            return $this->failure([],$return['code']);
        }
    }

}
