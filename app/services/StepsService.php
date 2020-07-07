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
    //update是否更新
    //0存在就不更新
    //1不同就更新
    //更新用户的步数
    public function updateStepsForUser($user_info,$steps = [],$update = 1)
    {
        $create = $update = 0;
        $steps = json_decode($steps['data'],true);
        $department = (new DepartmentService())->getDepartment($user_info->department_id);
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
                    if($update == 1)
                    {
                        $updateLog = $this->updateUserSteps($stepsInfo->log_id,$step['step'],$department);
                        if($updateLog)
                        {
                            $update ++;
                        }
                    }
                }
            }
            else
            {
                $createLog = $this->createUserSteps($user_info,$date,$step['step'],$department);
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
    //创建记录
    public function createUserSteps($user_info,$date,$step,$department)
    {
        $company_info = (new CompanyService())->getCompanyInfo($user_info->company_id,"company_id,detail");
        $company_info->detail = json_decode($company_info->detail);
        $currentTime = time();
        $steps = new \HJ\Steps();
        $steps->user_id = $user_info->user_id;
        $steps->company_id = $user_info->company_id;
        $steps->department_id = $user_info->department_id;
        $steps->daily_step = $company_info->detail->daily_step??$this->config->steps->defaultDailyStep;
        $steps->step = $step;
        $steps->date = $date;
        $steps->create_time = date("Y-m-d H:i:s",$currentTime);
        $steps->update_time = date("Y-m-d H:i:s",$currentTime);
        foreach($department as $key => $value)
        {
            if($key != "current_level")
            {
                $steps->$key = $value;
            }
        }
        $create = $steps->save();
        return $create;
    }
    //更新记录
    public function updateUserSteps($log_id,$step,$department)
    {
        $currentTime = time();
        $steps = (new \HJ\Steps())::findFirst("log_id = ".$log_id);
        foreach($department as $key => $value)
        {
            if($key != "current_level")
            {
                $steps->$key = $value;
            }
        }
        $steps->step = $step;
        $steps->update_time = date("Y-m-d H:i:s",$currentTime);
        return $steps->save();
    }

    public function refreshStepsCache($company_id = 1,$hours = 1)
    {
        $startTime = date("Y-m-d H:00:00",time()-$hours*3600);
        $dateList = (new \HJ\Steps())::find(["update_time >= '".$startTime."'","columns"=>"date,company_id","group"=>"date,company_id"])->toArray();
        $departmentStructure = (new DepartmentService())->getDepartmentStructure($company_id);
        echo "here";
        print_R($departmentStructure);
        foreach($dateList as $dateInfo)
        {
            $steps_to_update = $this->getStepsData($dateInfo['company_id'],$dateInfo['date']);
            print_R($steps_to_update);
            die();

        }
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
            $departmentStructure["all"][$key_1]["total"] = $list_1["count"]+$count;
            /*
            foreach($list_1['list'] as $key_2 => $list_2)
            {
                $departmentStructure["all"][$key_1]['list'][$key_2]['list_total'] = array_sum($list_2['list']);
            }
            */
        }
        print_R($departmentStructure["all"]);

        foreach($departmentStructure["all"] as $lv_1 => $lv_1_data)
        {
            $key = $company_id."_".$lv_1."_0_0_".date("Ymd",strtotime($date));
            echo "key:".$key."\n";
            foreach($lv_1_data["list"] as $lv_2 => $lv_2_data)
            {
                $key = $company_id."_".$lv_1."_".$lv_2."_0_".date("Ymd",strtotime($date));
                echo "key:".$key."\n";
                foreach($lv_2_data["list"] as $lv_3 => $lv_3_data)
                {
                    $key = $company_id."_".$lv_1."_".$lv_2."_".$lv_3."_".date("Ymd",strtotime($date));
                    echo "key:".$key."\n";
                }
            }

        }

    }

    public function getStepsData($company_id,$date,$group)
    {
        $steps = (new \HJ\Steps())::find(["company_id='".$company_id."' and date = '".$date."'","columns"=>"sum(step) as step,user_id,count(1) as count","group"=>"user_id"]);
        return $steps->toArray();
    }
    public function getStepsDataByDate($user_id,$dateRange,$company_id,$department_id,$group = "user_id",$page = 1,$pageSize = 3)
    {
        $key = md5(json_encode([$dateRange,$company_id,$department_id,$group]));
        $cache_settings = $this->config->cache_settings->steps_data;
        $redis_key = $cache_settings->name.$key;
        $cache = $this->redis->get($redis_key);
        //默认不拿数据库
        $from_db = 0;
        if(!$cache)
        {
            //缓存获取失败
            $from_db = 1;
        }
        else
        {
            $steps = json_decode($cache,true);
            if(count($steps)==0 || !is_array($steps))
            {
                //缓存为空，或者 是缓存解不开
                $from_db = 1;
            }
            else
            {
                //echo "cached";
            }
        }
        if($from_db == 1)
        {
            $whereCondition = "company_id = ".$company_id." ";

            if($department_id > 0)
            {
                $department = (new DepartmentService())->getDepartment($department_id);
                for($i = 1;$i<=$department['current_level'];$i++)
                {
                    $level_name = "department_id_".$i;
                    $whereCondition.= " and ".$level_name." = ".$department[$level_name];
                }
            }
            if(isset($dateRange['date']))
            {
                $whereCondition .= " and date = '".$dateRange['date']."'";
            }
            else
            {
                $whereCondition .= " and date > '".$dateRange['startDate']."' and date <= '".$dateRange['endDate']."'";
            }

            if(trim($group)!= "")
            {
                $params = [
                    $whereCondition,
                    "columns"=>$group.",sum(step) as totalStep,sum(if(step>daily_step,1,0)) as achives,sum(daily_step) as total_daily_step",
                    "group"=>$group,
                    "order"=>"totalStep DESC",
                    //        "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
                ];
            }
            else
            {
                $params = [
                    $whereCondition,
                    "columns"=>"sum(step) as totalStep,sum(if(step>daily_step,1,0)) as achives",
                    "order"=>"totalStep",
                    //      "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
                ];
            }
            $steps = (new \HJ\Steps())::find($params)->toArray();
            $this->redis->set($redis_key,json_encode($steps));
            $this->redis->expire($redis_key,$cache_settings->expire);
        }
        $start = ($pageSize-1)*$page;
        $end = ($start+$pageSize-1);
        $return = ["list"=>[],"mine"=>[]];
        foreach($steps as $key => $value)
        {
            if(($key+1) >=$start || ($key+1) <= $end)
            {
                $return["list"][] = $value;
            }
            elseif($key>$end)
            {
                break;
            }
        }
        $group = explode(",",$group??"");
        if(in_array("user_id",$group??[]))
        {
            $found = 0;
            foreach($steps as $key => $value)
            {
                if($value['user_id'] == "$user_id")
                {
                    $return["mine"] = $value;
                    $return["mine"]["rank"] = $key+1;
                    $found = 1;
                    break;
                }
            }
            if($found == 0)
            {
                $return["mine"] = [
                    'user_id'=>$user_id,
                    'totalStep'=>0,
                    'achives'=>0,
                    'total_daily_step'=>0,
                    'distance'=>0,
                    'kcal'=>0,
                    'time'=>0,
                    ];//{};
                $return["mine"]["Rank"] = count($return["list"])+1;
            }

        }


        return $return;
    }
    public function getUserStepsDataByDate($dateRange,$company_id,$user_id)
    {
        $whereCondition = "company_id = '".$company_id."' and user_id = '".$user_id."'";
        if(isset($dateRange['date']))
        {
            $whereCondition.= " and date = '".$dateRange['date']."'";
        }
        else
        {
            $whereCondition.= " and date >= '".$dateRange['startDate']."' and date <= '".$dateRange['endDate']."'";
        }
        $params = [
            $whereCondition,
            "columns"=>"date,sum(step) as totalStep",
            "group"=>"date",
            "order"=>"date DESC",
        ];
        $steps = (new \HJ\Steps())::find($params);
        return $steps->toArray();
    }

    public function generateTestSteps($company_id = 1,$month = 1)
    {
        $userList = (new \HJ\UserInfo())::find(["department_id>0 and company_id = ".$company_id,"columns"=>"user_id,department_id,company_id"]);
        for($i = 1;$i<=$month;$i++)
        {
            $month = date("m")-$i+1;
            $start_date = "2020-".$month."-01";
            echo $start_date;
            $end_date = date("Y-m-t",strtotime($start_date));
            echo $end_date;
            foreach($userList  as $userInfo)
            {
                $steps = ['data'=>["stepInfoList"=>[]]];
                for($i=0;$i<date("t",strtotime($end_date));$i++)
                {
                    $timeStamp = strtotime($start_date)+$i*86400;
                    $steps['data']['stepInfoList'][] = ['timestamp' => $timeStamp,"step"=>rand(1,9999)];
                }
                $steps['data'] = json_encode($steps['data']);
                $update = $this->updateStepsForUser($userInfo,$steps,0);
                echo $userInfo->user_id."\n";
                print_R($update);
                //$department = (new DepartmentService())->getDepartment($userInfo['department_id']);
                //print_R($department);
            }
        }

    }
    //获取当前匹配的健步走日期段
    //如果匹配不上，就是当前月
    public function getStepsDateRange($company_id,$date)
    {
        $where = "company_id = ".$company_id." and start_date <='".$date."' and end_date >= '".$date."'";
        $dateRange = (new \HJ\StepsDateRange())::findFirst([$where]);
        if(isset($dateRange->date_id))
        {
            $rangeStartDate = $dateRange->start_date;
            $rangeEndDate = $dateRange->end_date;
            $return = ["dateRange"=>["start_date"=>$dateRange->start_date,"end_date"=>$dateRange->end_date],"data"=>["day"=>["date"=>$date,"days"=>1],"week"=>[],"month"=>[]]];
            $days = ["week"=>7,"month"=>30];
            foreach($days as $key => $value)
            {
                $d = $rangeStartDate;
                $lag = intval((strtotime($date)-strtotime($rangeStartDate))/( $value * 86400 ));
                $return['data'][$key]['startDate'] = date("Y-m-d",strtotime($rangeStartDate) + $value * $lag * 86400);
                $return['data'][$key]['endDate'] = min($rangeEndDate,date("Y-m-d",strtotime($rangeStartDate) + $value * ($lag+1) * 86400));
                $return['data'][$key]['endDate'] = date("Y-m-d",strtotime($return[$key]['endDate'])-86400);
                $return['data'][$key]['days'] = intval((strtotime($return[$key]['endDate'])-strtotime($return[$key]['startDate']))/86400)+1;
            }
        }
        else
        {
            $week = (new Common())->processDateRange("week",1);
            $return = ["dateRange"=>[],'data'=>["day"=>["date"=>$date,"days"=>1],"week"=>["startDate"=>$week['startDate'],"endDate"=>$week['endDate']],"month"=>["startDate"=>date("Y-m-01",strtotime($date)),"endDate"=>date("Y-m-t",strtotime($date))]]];
        }
        foreach($return as $key => $value)
        {
            if(isset($value['startDate']))
            {
                $days = intval((strtotime($value['endDate'])-strtotime($value['startDate']))/86400);
                $return[$key]['days'] = $days+1;
            }
        }
        return $return;
    }
}