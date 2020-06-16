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
        $tem = [1, 2, 3]; //数据需要的channel_id
        $arr = [
            [
                'date' => "2020-06-09",
                'count' => 20,
                'channel_id' => 1
            ],
            [
                'date' => "2020-06-09",
                'count' => 2,
                'channel_id' => 2
            ],
            [
                'date' => "2020-06-10",
                'count' => 2,
                'channel_id' => 2
            ],
            [
                'date' => "2020-06-10",
                'count' => 2,
                'channel_id' => 3
            ],
        ];

        foreach ($arr as $key => $value) {
            $b[$value['date']][$value['channel_id']] = true;
        }
        foreach ($b as $k => $item) {
            foreach ($tem as $v) {
                if (array_key_exists($v, $item)) {
                    continue;
                } else {
                    $arr[]['date'] = $k;
                    $arr[key($arr)]['count'] = 0;
                    $arr[key($arr)]['channel_id'] = $v;
                }
            }


            print_r($arr);
            die();
            $return = (new TestService)->test();
            //$return = $oService->test();
            $this->logger->info(json_encode($return));

            return $this->success($return);
        }


    }
}
