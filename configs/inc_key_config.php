<?php
// +----------------------------------------------------------------------
// | 配置文件 参数配置
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     inc_config.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Logger;

$config = [
    'aliyun' =>
        [
            'oss'=>
            [
                'END_POINT'=>'oss-cn-shanghai.aliyuncs.com',
                'BUCKET'=>'xrace-pic',
                'ACCESS_KEY_ID'=>'LTAI4FkbExDy9cEfwqNfb93X',
                'ACCESS_KEY_SECRET'=>'57iMpXwB0UYR71tXGuacAHmoCDtTaL'
            ],
            'sms' =>
            [
                'accessKeyId' 	=> 'LTAIBfJoMGK90lWF',
                'accessSecret' 	=> 'KF1XNkmImdTGwNRtccDbJ909wkLu5w',
                'signName' 		=> 'willie',
            ]
    ]
];
return $config;