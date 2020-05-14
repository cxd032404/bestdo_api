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
        //接收参数并格式化
        $user_token = $this->request->getHeader('UserToken')?preg_replace('# #','',$this->request->getHeader('UserToken')):"";
        //调用user_token解密方法
        $return  = (new UserService)->getDecrypt($user_token);
        //返回值判断
        if($return['result']!=1){
            $page_info = (new PageService)->getPageBySign($company,$page_sign,"need_login");
            if(isset($page_info['need_login']) && $page_info['need_login']!=0){
                return $this->failure(['jump_url'=>'/login'],$return['msg'],$return['code']);
            }
        }
        /*用户token验证结束*/
	    /*
	    $arr = ['page'=>2,'page_size'=>4,'user'=>['name'=>"a","first_name"=>"b","family"=>['father'=>"dad",'mother'=>"mum"]]];
        $txt = json_encode($arr);
        */
        //echo json_encode($arr);
        $pageService = new PageService();
        $params = $this->request->getQuery();
        $params = $params['params']??"";
        //$params = $txt;
        $paramsCheck = $pageService->checkPageParams($params,$company,$page_sign);
        if(!$paramsCheck['result'])
        {
            return $this->failure($paramsCheck['detail']??[],$paramsCheck['code']);
        }
        $return  = $pageService->getPageInfo($company,$page_sign,$params);
        $this->logger->info(json_encode($return));
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
