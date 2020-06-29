<?php
use HJ\Company;
use HJ\Protocal;

class CompanyService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$company_id：企业id
    //cloumns：数据库的字段列表
    public function getCompanyInfo($company_id,$columns = "company_name,company_id,icon,detail")
    {
        return (new Company())->findFirst(
            [
                "company_id = $company_id",
                "columns" => $columns
            ]);
    }
    //获取企业列表
    //cloumns：数据库的字段列表
    public function getCompanyList($columns = "company_name,company_id,icon")
    {
        return (new Company())->find(
            [
                "columns" => $columns
            ]);
    }
    //根据获取对应企业的协议信息
    //$company_id：企业id
    //$type：协议类型  user用户协议｜privacy用户政策
    //cloumns：数据库的字段列表
    public function getCompanyProtocal($company_id,$type = "user",$columns = "protocal_id,content")
    {
        $params =
        [
            "company_id = '".$company_id."' and type= '".$type."'",
            "columns" => $columns
        ];
        return (new Protocal())->findFirst($params);
    }
}