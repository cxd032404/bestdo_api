<?php
use HJ\ListModel;
class ListService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListInfo($list_id,$columns = "list_id,company_id,detail")
    {
        return (new ListModel())->findFirst(
            [
                "list_id = $list_id",
                "columns" => $columns
            ]);
    }
    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListByActivity($activity_id,$columns = "list_id,company_id,detail")
    {
        return (new ListModel())->find(
            [
                "activity_id = $activity_id",
                "columns" => $columns
            ]);
    }

    public function processAfterPostAction($list_id,$user_id,$detail)
    {
        $return = ['after_url'=>'','params'=>[]];
        $detail['after_action'] = $detail['after_action']??0;
        $detail['after_url'] = str_replace("#list_id#",$list_id,$detail['after_url']??"");
        $detail['after_url'] = str_replace("#user_id#",$user_id,$detail['after_url']??"");
        $t = explode("|",$detail['after_url']??"");

        foreach($t as $key => $value)
        {
            if($key==0)
            {
                $return['after_url'] = trim($value);
            }
            else
            {
                $t2 = explode("=",trim($value));
                $return['params'][] = ["key"=>trim($t2[0]),'value'=>trim($t2[1]??"")];
            }
        }
        return $return;
    }
}