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

class StepsService extends BaseService
{
    //更新用户的步数
    public function updateStepsForUser($user_info,$steps = [])
    {
        $create = $update = 0;
        $steps = json_decode($steps['data'],true);
        foreach($steps['stepInfoList'] as $date => $step)
        {
            $date = date("Y-m-d",$step['timestamp']);
            //查找记录
            $stepsInfo = $this->getUserSteps($user_info->user_id,$date);
            if(isset($stepsInfo->log_id))
            {
                if($step == $stepsInfo->step)
                {

                }
                else
                {
                    $updateLog = $this->updateUserSteps($stepsInfo->log_id,$step['step']);
                    if($updateLog)
                    {
                        $update ++;
                    }
                }

            }
            else
            {
                $createLog = $this->createUserSteps($user_info,$date,$step['step']);
                if($createLog)
                {
                    $create ++;
                }
            }
        }
        return ['result'=>1,'create'=>$create,'update'=>$update];
    }

    public function getUserSteps($user_id,$date)
    {
        $steps = (new \HJ\Steps())::findFirst(["user_id='".$user_id."' and date = '".$date."'"]);
        return $steps;
    }

    public function createUserSteps($user_info,$date,$step)
    {
        $company_info = (new CompanyService())->getCompanyInfo($user_info->company_id,"company_id,detail");
        $company_info->detail = json_decode($company_info->detail);
        $currentTime = time();
        $steps = new \HJ\Steps();
        $steps->user_id = $user_info->user_id;
        $steps->company_id = $user_info->company_id;
        $steps->department_id = $user_info->department_id;
        $steps->deily_step = $company_info->detail->daily_step??5000;
        $steps->step = $step;
        $steps->date = $date;
        $steps->create_time = date("Y-m-d H:i:s",$currentTime);
        $steps->update_time = date("Y-m-d H:i:s",$currentTime);
        $create = $steps->save();
        return $create;
    }

    public function updateUserSteps($log_id,$step)
    {
        $currentTime = time();
        $steps = (new \HJ\Steps())::findFirst("log_id = ".$log_id);
        $steps->step = $step;
        $steps->update_time = date("Y-m-d H:i:s",$currentTime);
        return $steps->save();
    }

    public function refreshStepsCache($company_id = 1,$hours = 3)
    {
        $departmentStructure = (new DepartmentService())->getDepartmentStructure($company_id);
        $steps_to_update = $this->getStepsData($company_id,"");
        foreach($steps_to_update as $step)
        {
            $userInfo = (new UserService())->getUserInfo($step['user_id'],"user_id,department_id");
            echo $userInfo->user_id."-".$userInfo->department_id."-".$step["step"]."\n";
            if(isset($userInfo->department_id))
            {
                $parent = $departmentStructure["map"][$userInfo->department_id];
                if($parent==0)
                {
                    //echo "level_1:".$userInfo->department_id."\n";
                    $departmentStructure["all"][$userInfo->department_id]["count"]+=$step['step'];
                }
                elseif($departmentStructure["map"][$parent]==0)
                {
                    //echo "level_2:".$userInfo->department_id."\n";
                    $departmentStructure["all"][$parent]['list'][$userInfo->department_id]["count"]+=$step['step'];
                }
                else
                {
                    $parent_2 = $departmentStructure["map"][$parent];
                    //echo "level_3:".$userInfo->department_id."\n";
                    $departmentStructure["all"][$parent_2]['list'][$parent]['list'][$userInfo->department_id]+=$step['step'];
                }

            }
        }
        foreach($departmentStructure["all"] as $key_1 => $list_1)
        {
            foreach($list_1['list'] as $key_2 => $list_2)
            {
                $count = array_sum($list_2['list']);
                $departmentStructure["all"][$key_1]['list'][$key_2]['list_total'] = $count;
                $departmentStructure["all"][$key_1]['list'][$key_2]['total'] = $count+$list_2['count'];

            }
        }
        foreach($departmentStructure["all"] as $key_1 => $list_1)
        {
            $count = array_sum(array_column($list_1['list'],"total"))."\n";
            //echo "count:".$count."\n";
            //print_R($list_1);
            $departmentStructure["all"][$key_1]["list_total"] = $count;
            $departmentStructure["all"][$key_1]["total"] = $list_1["count"];
            /*
            foreach($list_1['list'] as $key_2 => $list_2)
            {
                $departmentStructure["all"][$key_1]['list'][$key_2]['list_total'] = array_sum($list_2['list']);
            }
            */
        }
        print_R($departmentStructure["all"]);

    }

    public function getStepsData($company_id,$date)
    {
        $steps = (new \HJ\Steps())::find(["company_id=".$company_id,"columns"=>"sum(step) as step,user_id,count(1) as count","group"=>"user_id"]);
        return $steps->toArray();
    }



}