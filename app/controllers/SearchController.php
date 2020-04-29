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
        $index_name = "company_user_list".$company_id;
        $pa =
            [
                'index'=>'company_user_list_'.$company_id,
                'type'=>'company_user_list',
                'body'=>
                    ['query'=>
                        ['bool'=>
                            ['must'=>
                                [
                                    ['multi_match'=>
                                        [
                                            'query'=>$query,
                                            "type"=>"most_fields",
                                            "analyzer"=>"ik_smart",
                                            'fields'=>['name','mobile','worker_id']
                                        ]],
                                    ['term'
                                     => ['company_id'=>$company_id]]
                                ],
                            ]
                        ],
                        "from"=>($page-1)*$page_size,
                        "size"=>$page_size,
                        'highlight' => [
                            'pre_tags' => ["<em>"],
                            'post_tags' => ["</em>"],
                            'fields' => [
                                "name" => new \stdClass()
                            ]
                        ]
                    ]
            ];
        $search_return = json_decode(json_encode($client->search($pa)),true);
        $search_return_list = array_column($search_return['hits']['hits'],'_source');
        //日志记录
        $this->logger->info(json_encode($search_return_list));
        return $this->success(['company_user_list'=>$search_return_list]);
        //$search_return = json_decode(json_encode($this->elasticsearch->search($pa)),true);
        //return $this->success($search_return['hits']['hits']);
    }
    public function testAction()
    {
        /*


        $index = "ik_test";
        $params = [
            'index' => $index,
        ];
        $response = $client->indices()->exists($params);

        if($response)
        {
            $response = $client->indices()->get($params);
        }
        else
        {
            echo "create\n";
            $params = [
                'index' => $index,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 2,
                        'number_of_replicas' => 0
                    ]
                ]
            ];
            $create = $client->indices()->create($params);
            $params = ["index"=>$index,"type"=>$index,
                'body'=>[
                    'properties'=>[
                        "id"=>["type"=>"text",
                            "analyzer"=>"ik_max_word",
                            "search_analyzer"=>"ik_max_word"],
                        "txt"=>["type"=>"text",
                            "analyzer"=>"ik_max_word",
                            "search_analyzer"=>"ik_max_word"],

                    ]
                ]];
            $map = $client->indices()->putMapping($params);
        }
        $txtArr = ["中国","美国","法国","德国","英国","瑞典","瑞士","西班牙","波兰","荷兰","比利时"];
        $txtArr2 = ["动物","植物","人","木材","金属","粮食","蔬菜","稀土"];
        for($i = 1;$i<=10;$i++)
        {
            $i1 = array_rand($txtArr);
            $i2 = array_rand($txtArr2);
            $data = ['txt'=>$txtArr[$i1]."的".$txtArr2[$i2],
                'id'=>sprintf("%02d",$i1)."_".sprintf("%02d",$i2)];
            $data = ["index"=>$index,"type"=>$index,"id"=>$data['id'],"body"=>$data];
            $indexResult = $client->index($data);
        }
        $pa =
            [
                'index'=>$index,
                'type'=>$index,
                'body'=>
                    ['query'=>
                        ['bool'=>
                            ['must'=>
                                [
                                    ['multi_match'=>
                                        [
                                            'query'=>$query,
                                            "type"=>"most_fields",
                                            "analyzer"=>"ik_max_word",
                                            'fields'=>['txt','id']
                                        ]],
                                ],
                            ]
                        ],
                        "from"=>($page-1)*$page_size,
                        "size"=>$page_size,
                        'highlight' => [
                            'pre_tags' => ["<em>"],
                            'post_tags' => ["</em>"],
                            'fields' => [
                                "txt" => new \stdClass(),
                                "id" => new \stdClass()

                            ]
                        ]
                    ]
            ];
        $search_return = json_decode(json_encode($this->elasticsearch->search($pa)),true);
        print_R($search_return);
        die();
        */
    }

}
