<?php
use HJ\Vote;
class VoteService extends BaseService
{
	private $msg = 'success';

    //根据投票ID获取投票
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getVote($vote_id,$columns = "vote_id,detail")
    {
        $params =             [
            "vote_id = $vote_id ",
            "columns" => $columns
        ];
        return (new Vote())->findFirst($params);
    }
}