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
        $userInfo = (new \HJ\UserInfo())::find(["company_id = ".$company_id,"limit"=>3]);
        print_R($userInfo->toArray());
        $allDepartment = (new \HJ\Department())::find(["company_id = ".$company_id]);
        print_R($allDepartment->toArray());
        die();
        $departmentStructure = (new DepartmentService())->getDepartmentStructure($company_id);
        print_R($departmentStructure);
    }



}