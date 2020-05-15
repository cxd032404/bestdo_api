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
	public function companyUserAction( $company_id = 0,$query = "",$page=1,$page_size=10 )
	{
        $client = $this->elasticsearch;
        $index_name = "company_user_list_".$company_id;
        $pa =
            [
                'index'=>'company_user_list_'.$company_id,
                'type'=>'company_user_list',
                'body'=>
                    ['query'=>
                        ['bool'=>
                            [
                                'must'=>
                                    [
                                        [
                                            'term'=>
                                                [
                                                    'company_id'=>$company_id
                                                ]
                                        ]
                                    ],
                                'should'=>
                                [

                                    [
                                        'multi_match'=>
                                            [
                                                'query'=>$query,
                                                "type"=>"most_fields",
                                                //"analyzer"=>"ik_smart",
                                                'fields'=>['name','mobile','worker_id']
                                            ]
                                    ],
                                    [
                                        'wildcard'=>
                                            [
                                                'name'=>'*'.$query."*",
                                            ]
                                    ],
                                    [
                                        'wildcard'=>
                                            [
                                                'mobile'=>'*'.$query."*",
                                            ]
                                    ],
                                ],
                                'minimum_should_match'=>1

                            ]
                        ],
                        "from"=>($page-1)*$page_size,
                        "size"=>$page_size,
                        'highlight' => [
                            'pre_tags' => ["<em>"],
                            'post_tags' => ["</em>"],
                            'fields' => [
                                "name" => new \stdClass(),
                                "mobile" => new \stdClass(),
                            ]

                        ],
                        'sort'=>['_score'=>["order"=>"desc"]]
                    ]
            ];
        $search_return = json_decode(json_encode($client->search($pa)),true);
        $search_return_list = array_column($search_return['hits']['hits'],'_source');
        //日志记录
        $this->logger->info(json_encode($search_return_list));
        return $this->success(['company_user_list'=>$search_return_list]);
    }
    /*
  * 联想输入获取faq提问
  * 参数
  * activity_id（必填）：活动对应ID
  * query（必填）：关键字
  * page（选填）：分页数
  * page_size（选填）：每页条数
  * */
    public function faqAction( $activity_id = 0,$query = "",$page=1,$page_size=10 )
    {
        $client = $this->elasticsearch;
        $index_name = 'question_list_'.$activity_id;
        $pa =
            [
                'index'=>'question_list_'.$activity_id,
                'type'=>'question_list',
                'body'=>
                    ['query'=>
                        ['bool'=>
                            [
                                'must'=>
                                    [
                                        [
                                            'term'=>
                                                [
                                                    'activity_id'=>$activity_id
                                                ]
                                        ]
                                    ],
                                'should'=>
                                    [
                                        [
                                            'multi_match'=>
                                                [
                                                    'query'=>$query,
                                                    "type"=>"most_fields",
                                                    //"analyzer"=>"ik_smart",
                                                    'fields'=>['question','answer','detail.keywords']
                                                ]
                                        ],
                                    ],
                                'minimum_should_match'=>1
                            ]
                        ],
                        "from"=>($page-1)*$page_size,
                        "size"=>$page_size,
                        'highlight' => [
                            'pre_tags' => ["<em>"],
                            'post_tags' => ["</em>"],
                            'fields' => [
                                "name" => new \stdClass(),
                                "mobile" => new \stdClass(),
                            ]

                        ],
                        'sort'=>['_score'=>["order"=>"desc"]]
                    ]
            ];
        $search_return = json_decode(json_encode($client->search($pa)),true);
        $search_return_list = array_column($search_return['hits']['hits'],'_source');
        //日志记录
        $this->logger->info(json_encode($search_return_list));
        return $this->success(['question_list'=>$search_return_list]);
    }

}
