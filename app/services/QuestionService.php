<?php
// +----------------------------------------------------------------------
// | AccountService
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     AccountService.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Robots as robotModel;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\User\Component;
use Elasticsearch\ClientBuilder;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;

class QuestionService extends BaseService
{
    public function searchForQuestion($params)
    {
        $searchParams = [
            "activity_id = '".$params['activity_id']."' and ((question like '%".$params['query']."%') or (answer like '%".$params['query']."%'))",
            "columns" => "*",
            "order" => "question_id desc",
            "limit" => ["offset"=>($params['page']-1)*$params['page_size'],"number"=>$params['page_size']]
        ];
        $userList = \HJ\Question::find($searchParams);
        return $userList;
    }



	
}