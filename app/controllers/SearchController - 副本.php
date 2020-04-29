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

class SearchController extends BaseController
{

    /*
     * 核对用户身份
     * 参数
     * company_id（必填）：企业对应ID
     * query（必填）：用户名称
     * page（选填）：分页数
     * page_size（选填）：每页条数
     * */
	public function companyUserAction( $company ="",$query = "",$page=1,$page_size=10 )
	{
        $client = $this->elasticsearch;
        $pa =
            [
                'index'=>'company_user_'.$company,
                'type'=>'company_user',
                'body'=>
                    ['query'=>
                        ['bool'=>
                            ['must'=>
                                [
                                    ['multi_match'=>
                                        [
                                            'query'=>$query,
                                            "type"=>"most_fields",
                                            'fields'=>['name','mobile','worker_id']
                                        ]],
                                    ['term'
                                     => ['company_id'=>$company]]
                                ],
                            ]
                        ],
                        "from"=>($page-1)*$page_size,
                        "size"=>$page_size,
                    ]
            ];
        $search_return = json_decode(json_encode($client->search($pa)),true);
        $search_return_list = array_column($search_return['hits']['hits'],'_source');
        //日志记录
        $this->logger->info(json_encode($search_return_list));
        return $this->success(['company_user_list'=>$search_return_list]);
    }

}
