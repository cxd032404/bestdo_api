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
        (new StepsService())->generateTestSteps(6);
        die();
        (new StepsService())->refreshStepsCache();

        $return = (new WechatMessageService())->sendMessage1();

        die();

         $return = (new WechatMessageService())->sendMessage(['club_id'=>1,'user_id'=>11907],'clubJoin');
         echo 1;
         print_r($return);die();
            $return = (new TestService)->test();
            //$return = $oService->test();
            $this->logger->info(json_encode($return));

            return $this->success($return);
        }



}
