<?php
use HJ\Company;
class CompanyService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getCompanyInfo($list_id,$columns = "company_name,company_id,icon")
    {
        return (new Company())->findFirst(
            [
                "company_id = company_id",
                "columns" => $columns
            ]);
    }
}