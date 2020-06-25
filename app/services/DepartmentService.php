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
                $allDepartment[$department_id] = ["count"=>0,"total"=>0,"list_total"=>0,"list"=>[]];
                unset($map[$department_id]);
            }
        }
        foreach($map as $department_id => $parent)
        {
            if(isset($allDepartment[$parent]))
            {
                $allDepartment[$parent]["list"][$department_id] = ["count"=>0,"total"=>0,"list_total"=>0,"list"=>[]];
                //unset($map[$department_id]);
            }
        }
        foreach($map as $department_id => $parent)
        {
            if(isset($map[$parent]) && $map[$parent] > 0)
            {
                $allDepartment[$map[$parent]]["list"][$parent]["list"][$department_id] = 0;
                unset($map[$department_id]);
            }
        }
        return ["all"=>$allDepartment,"map"=>array_combine(array_column($departmentList,"department_id"),array_column($departmentList,"parent_id"))];
    }
    public function getDepartment($department_id)
    {
        $departmentInfo = $this->getDepartmentInfo($department_id);
        if($departmentInfo->parent_id==0)
        {
            //第一级
            $return = ['department_id_1'=>$department_id,"department_id_2"=>0,"department_id_3"=>0];
        }
        else
        {
            $parentDepartmentInfo = $this->getDepartmentInfo($departmentInfo->parent_id);
            if($parentDepartmentInfo->parent_id==0)
            {
                //第二级
                $return = ["department_id_1"=>$departmentInfo->parent_id,'department_id_2'=>$department_id,"department_id_3"=>0];
            }
            else
            {
                //第二级
                $return = ["department_id_1"=>$parentDepartmentInfo->parent_id,'department_id_2'=>$departmentInfo->parent_id,"department_id_3"=>$department_id];
            }
        }
        return $return;
    }

    //获取用户信息
    public function getDepartmentInfo($department_id,$columns = 'department_id,company_id,parent_id',$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->department_info;
        $cacheName = $cacheSetting->name.$department_id;
        $params =             [
            "department_id='".$department_id."'",
            'columns'=>'*',
        ];
        if($cache == 0)
        {
            //获取部门信息
            $departmentInfo = \HJ\Department::findFirst($params);
            if(isset($departmentInfo->department_id))
            {
                $this->redis->set($cacheName,json_encode($departmentInfo));
                $this->redis->expire($cacheName,3600);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($cache->department_id))
            {
                $departmentInfo = $cache;
            }
            else
            {
                //获取部门信息
                $departmentInfo = \HJ\Department::findFirst($params);
                if(isset($departmentInfo->department_id))
                {
                    $this->redis->set($cacheName,json_encode($departmentInfo));
                    $this->redis->expire($cacheName,3600);
                }
                else
                {
                    return [];
                }
            }
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($departmentInfo as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($departmentInfo->$key);
                }
            }
        }
        return $departmentInfo;
    }
}