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
    public function testAction($id = 0)
    {
        $data = (new ActivityService())->getActivityMemberList(4)->toArray();
        print_r($data);die();
        die();
        print_r(mb_strlen('史说政'));
        print_r(strlen('史说政'));
        die();
        $data = (new ActivityService())->getActivityInfo(4)->toArray();
        print_r($data);die();

            $return = (new TestService)->test();
            //$return = $oService->test();
            $this->logger->info(json_encode($return));

            return $this->success($return);
        }






}
