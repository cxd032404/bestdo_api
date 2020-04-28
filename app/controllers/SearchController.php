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
use Elasticsearch\ClientBuilder;

class SearchController extends BaseController
{

	public function companyUserAction( $company ="",$query = "" )
	{
        //echo "company:".$company;
        //echo "query:".$query;
        $client = ClientBuilder::create()->setHosts(["192.168.31.155:9200"])->build();
        $pa =
            [
                'index'=>'company_user',
                'type'=>'company_user',
                'body'=>
                    ['query'=>
                        ['bool'=>
                            ['must'=>
                                ['multi_match'=>
                                    ['query'=>$query,'fields'=>['name','mobile','worker_id']]
                                ],
                                //['term'=>['company_id'=>$comp]]
                            ]
                        ]
                    ]
            ];
        $search_return = json_decode(json_encode($client->search($pa)),true);
        return $this->success($search_return['hits']['hits']);
    }

}
