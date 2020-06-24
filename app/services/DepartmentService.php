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

class DepartmentService extends BaseService
{
    public function getDepartmentStructure($company_id)
    {
        $allDepartment = [];
        $departmentList = (new \HJ\Department())::find(["company_id = ".$company_id])->toArray();
        $map = array_combine(array_column($departmentList,"department_id"),array_column($departmentList,"parent_id"));
        foreach($map as $department_id => $parent)
        {
            if($parent == 0)
            {
                $allDepartment[$department_id] = ["count"=>0,"total"=>0,"list"=>[]];
                //unset($map[$department_id]);
            }
        }
        foreach($map as $department_id => $parent)
        {
            if(isset($allDepartment[$parent]))
            {
                $allDepartment[$parent]["list"][$department_id] = ["count"=>0,"total"=>0,"list"=>[]];
                //unset($map[$department_id]);
            }
        }
        foreach($map as $department_id => $parent)
        {
            if(isset($map[$parent]))
            {
                $allDepartment[$map[$parent]]["list"][$parent]["list"][$department_id] = 0;
                //unset($map[$department_id]);
            }
        }
        return ["all"=>$allDepartment,"map"=>$map];
    }
}