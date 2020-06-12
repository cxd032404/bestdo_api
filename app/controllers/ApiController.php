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
	    $data = (new ClubService())->getClubMemberLogInfo(1,'*',0,1,2)->toArray();
	    print_r($data);die();


        $data = (new WechatController())->sendMessage('oPCk01ftkc3CX4bybn_bVOWylKr8','-Qq05dZSlDIf7LyuSWf0V3tJ9AuXjypdempKDTSGUio','史说政的测试信息');
            print_r($data);die();
	    (new WechatController())->sendMessage('oPCk01ftkc3CX4bybn_bVOWylKr8',1,'史说政的测试信息');
        die();
        $data = (new listService())->getListInfo(1,'list_type,list_id');
        print_r($data);die();
	    $return  = (new TestService)->test();
        //$return = $oService->test();
        $this->logger->info(json_encode($return));

        return $this->success($return);
    }



}
