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
            $return = ['department_id_1'=>$department_id,"department_id_2"=>0,"department_id_3"=>0,"current_level"=>1];
        }
        else
        {
            $parentDepartmentInfo = $this->getDepartmentInfo($departmentInfo->parent_id);
            if($parentDepartmentInfo->parent_id==0)
            {
                //第二级
                $return = ["department_id_1"=>$departmentInfo->parent_id,'department_id_2'=>$department_id,"department_id_3"=>0,"current_level"=>2];
            }
            else
            {
                //第三级
                $return = ["department_id_1"=>$parentDepartmentInfo->parent_id,'department_id_2'=>$departmentInfo->parent_id,"department_id_3"=>$department_id,"current_level"=>3];
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

    /*
     * 获取子部门
     */
    public function getDepartmentListByParent($company_id,$parent_id,$columns = 'department_id,department_name',$cache = 1){
        $redisKey = $this->config->cache_settings->department_parent;
        $redisKey_name = $redisKey->name.'company_id:'.$company_id.'parment_id:'.$parent_id;
        if($cache == 1)
        {
             $res = $this->redis->get($redisKey_name);
             $redis_data = json_decode($this->redis->get($redisKey_name));
             if($redis_data)
             {
               $departmentInfo = $redis_data;
             }else
             {
                 $conditions = 'company_id = '.$company_id.' and parent_id = '.$parent_id;
                 $params = [
                     $conditions,
                     'columns'=>'*'
                 ];
                 $departmentInfo = (new \HJ\Department())->find($params);
                 $this->redis->set($redisKey_name,json_encode($departmentInfo));
                 $this->redis->expire($redisKey_name,$redisKey->expire);
                 $departmentInfo = json_decode($this->redis->get($redisKey_name));
             }
        }else
        {
            $conditions = 'company_id = '.$company_id.' and parent_id = '.$parent_id;
            $params = [
                $conditions,
                'columns'=>'*'
            ];
            $departmentInfo = (new \HJ\Department())->find($params);
            $this->redis->set($redisKey_name,json_encode($departmentInfo));
            $this->redis->expire($redisKey_name,$redisKey->expire);
            $departmentInfo = json_decode($this->redis->get($redisKey_name));
        }
        $departmentInfo = json_decode(json_encode($departmentInfo),true);
        if($columns != '*')
        {
            $temp =  explode(',',$columns);
            foreach ($departmentInfo as $key =>$value)
            {
                foreach ($value as $k=>$v)
                {
                    if(!in_array($k,$temp))
                    {
                        unset($departmentInfo[$key][$k]);
                    }
                }
            }
        }
        $departmentInfo = json_decode(json_encode($departmentInfo));
        return $departmentInfo;
    }

    /*
     * 获取公司所有部门
     */
    public function getCompanyDepartment($company_id){
        $department = (new \HJ\Department())->find(['company_id ='.$company_id,'columns'=>'department_id,parent_id,department_name']);
        if($department) {
            $department = $department->toArray();
            $department = array_column($department,null,'department_id');
            //print_r($department);die();
            $tree = [];
            foreach ($department as $key => $value) {
                if($value['parent_id']==0)
                {
                    $tree[] = &$department[$value['department_id']];
                }else
                {
                    $department[$value['parent_id']]['child'][] = &$department[$key];
                }
            }
        }
        return $tree;




    }




















}