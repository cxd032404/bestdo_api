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
}