<?php
use HJ\Activity;
use HJ\UserInfo;
class ActivityService extends BaseService
{
    private $msgList = [
        "activity_empty"=>"活动无效，请选择正确的活动！",
        "activity_apply_not_started"=>"活动尚未开启报名，请耐心等待！",
        "activity_ended"=>"活动已结束，不可报名！",
        "activity_expire"=>"当前时间不在报名时间内！",
        "activity_cancelled"=>"活动已取消！",
        "activity_apply_success"=>"报名成功！",
        "activity_apply_fail"=>"报名失败！",
        "activity_checkin_success"=>"签到成功！",
        "activity_checkin_fail"=>"签到失败！",
        "checkin_over_distance"=>"距离过远",
        "activity_log_not_found"=>"没有找到报名记录",
        "activity_update_success"=>"活动更新成功",
        "activity_update_fail"=>"活动更新失败",
        "activity_member_limit"=>"活动人数已满",
        "activity_club_limit"=>"申请加入",
    ];
    //生成每周的重复性活动数据
    public function generateNextActivityWeekly($activityParams)
    {
        $range_end_time = $activityParams['weekly_rebuild']['end_date']." 23:59:59";
        $return = [];
        $i = 0;
        $end_time = strtotime($activityParams['end_time']);
        while($end_time <= strtotime($range_end_time))
        {
            $end_time += 7*86400;
            $nextParam = [];
            $nextParam['start_time'] = date("Y-m-d H:i:s",strtotime($activityParams['start_time'])+$i*7*86400);
            $nextParam['end_time'] = date("Y-m-d H:i:s",strtotime($activityParams['end_time'])+$i*7*86400);
            $nextParam['appay_start_time'] = date("Y-m-d H:i:s",strtotime($activityParams['apply_start_time'])+$i*7*86400);
            $nextParam['apply_end_time'] = date("Y-m-d H:i:s",strtotime($activityParams['apply_end_time'])+$i*7*86400);
            $i++;
            $return[] = $nextParam;
        }
        return $return;

    }
    public function generateTimeForActivity($activityParams)
    {
        $oCommon = new Common();
        //$activityParams['weekly_rebuild'] = json_decode($activityParams['weekly_rebuild'],true);
        $currentDay = date("w",time());
        if(!isset($activityParams['weekly_rebuild']['start_weekday'])
            || !isset($activityParams['weekly_rebuild']['start_weekday'])
            || !isset($activityParams['weekly_rebuild']['start_weekday'])
            || !isset($activityParams['weekly_rebuild']['start_weekday'])
            || !isset($activityParams['weekly_rebuild']['start_weekday'])
            || !isset($activityParams['weekly_rebuild']['start_weekday']))
        {
            return false;
        }
        $currentDate = date('Y-m-d');
        $applyStartDate = $oCommon->getNextWeekday($currentDate,$activityParams['weekly_rebuild']['apply_start_weekday']);
        $applyEndDate = $oCommon->getNextWeekday($applyStartDate,$activityParams['weekly_rebuild']['apply_end_weekday']);
        $startDate = $oCommon->getNextWeekday($applyStartDate,$activityParams['weekly_rebuild']['start_weekday']);
        $endDate = $oCommon->getNextWeekday($startDate,$activityParams['weekly_rebuild']['end_weekday']);
        $activityParams['start_time'] = $startDate." ".$activityParams['weekly_rebuild']['start_time'];
        $activityParams['end_time'] = $endDate." ".$activityParams['weekly_rebuild']['end_time'];
        $activityParams['apply_start_time'] = $applyStartDate." 00:00:00";
        $activityParams['apply_end_time'] = $applyEndDate." 23:59:59";
        return $activityParams;

    }

    public function createActivity($activityParams = [],$user_info)
    {
        $currentTime = time();

        //只有有重复参数且头部为0
        $nextList = [];
        if(isset($activityParams['weekly_rebuild']) && is_array($activityParams['weekly_rebuild']) && $activityParams['connect_activity_id'] == 0)
        {
            $activityParams = $this->generateTimeForActivity($activityParams);
            if(!$activityParams)
            {
                $return  = ['result'=>0,"msg"=>"活动时间有误，请重新输入",'code'=>400];
            }
            else
            {
                $nextList = $this->generateNextActivityWeekly($activityParams);
            }
        }
        //活动时间校验

        if($activityParams['start_time']=="" || $activityParams['end_time']=="")
        {
            $return  = ['result'=>0,"msg"=>"活动时间有误，请重新输入",'code'=>400];
        }
        //活动名称长度校验
        elseif(strlen($activityParams['activity_name'])>=32)
        {
            $return  = ['result'=>0,"msg"=>"活动名称超长，请重新输入",'code'=>400];
        }//说明长度
        elseif(strlen($activityParams['comment'])>=2000)
        {
            $return  = ['result'=>0,"msg"=>"活动说明超长，请重新输入",'code'=>400];
        }
        //报名时间校验
        elseif($activityParams['apply_start_time']=="" || $activityParams['apply_end_time']=="")
        {
            $return  = ['result'=>0,"msg"=>"报名时间时间有误，请重新输入",'code'=>400];
        }
        elseif(strtotime($activityParams['end_time']<$currentTime))
        {
            $return  = ['result'=>0,"msg"=>"活动结束时间不可早于当前时间，请重新输入",'code'=>400];
        }
        elseif($activityParams['activity_name'] == "")
        {
            $return  = ['result'=>0,"msg"=>"活动名称有误，请重新输入",'code'=>400];
        }
        else
        {
            //检查对当前俱乐部的权限
            $permission = (new ClubService())->getUserClubPermission($user_info->user_id,$activityParams['club_id'],1);
            if(intval($permission) == 0)
            {
                $return  = ['result'=>0,"msg"=>"您没有执行此操作的权限哦",'code'=>403];
            }
            else
            {
                /*
                //检查名称是否重复
                $nameExists = $this->getActivityByName($activityParams['activity_name'],$activityParams['end_time'],$user_info->company_id);
                if(isset($nameExists->activity_id))
                {
                    $return  = ['result'=>0,"msg"=>"活动名称已经存在了哦，请重新输入",'code'=>400];
                }
                else
                {
                */
                    //$activityParams['weekly_rebuild'] = ($activityParams['weekly_rebuild']>=0 && $activityParams['weekly_rebuild']<=6)?$activityParams['weekly_rebuild']:-1;
                    //写入数据
                    $activity = new Activity();
                    $activity->activity_name = $activityParams['activity_name'];
                    $activity->comment = $activityParams['comment'];
                    $activity->club_id = $activityParams['club_id'];
                    $activity->company_id = $user_info->company_id;
                    $activity->create_user_id = $user_info->user_id;
                    $activity->start_time = $activityParams['start_time'];
                    $activity->end_time = $activityParams['end_time'];
                    $activity->apply_start_time = $activityParams['apply_start_time'];
                    $activity->apply_end_time = $activityParams['apply_end_time'];
                    $activity->create_time = date("Y-m-d H:i:s",$currentTime);
                    $activity->update_time = date("Y-m-d H:i:s",$currentTime);
                    $activity->club_member_only = $activityParams['club_member_only'];
                    $activity->member_limit = $activityParams['member_limit'];
                    $activity->icon = "";
                    $activity->activity_sign = "";
                    $activity->connect_activity_id = $activityParams['connect_activity_id'];
                    $activity->status = 1;
                    $activity->detail = json_encode(
                        [
                            "checkin"=>$activityParams['checkin']??[],
                            "monthly_apply_limit"=>$activityParams['monthly_apply_limit'],
                            "weekly_rebuild"=>$activityParams['weekly_rebuild']??-1
                            ]
                    );
                    $create = $activity->create();
                    if ($create === false)
                    {
                        $return  = ['result'=>0,"msg"=>"活动创建失败，请稍后再试",'code'=>400];
                    }
                    else
                    {
                        //从头创建模式
                        if($activityParams['connect_activity_id'] == 0)
                        {
                            foreach($nextList as $key => $value)
                            {
                                $copyActivity = array_merge($activityParams,$value);
                                $copyActivity['connect_activity_id'] = $copied['activity_id']??$activity->activity_id;
                                $copied = $this->createActivity($copyActivity,$user_info);
                            }
                        }
                        $return  = ['result'=>1,"msg"=>"活动创建成功！",'activity_id'=>$activity->activity_id,'data'=>$this->getActivityInfo($activity->activity_id,"*",0),'code'=>200];
                    }
               //}
            }
        }
        return $return;
    }
    public function updateActivity($activityId,$activityParams = [],$user_info)
    {
        $currentTime = time();
        //活动时间校验
        if($activityParams['start_time']=="" || $activityParams['end_time']=="")
        {
            $return  = ['result'=>0,"msg"=>"活动时间有误，请重新输入",'code'=>400];
        }
        //报名时间校验
        elseif($activityParams['apply_start_time']=="" || $activityParams['apply_end_time']=="")
        {
            $return  = ['result'=>0,"msg"=>"报名时间时间有误，请重新输入",'code'=>400];
        }
        elseif(strtotime($activityParams['end_time']<$currentTime))
        {
            $return  = ['result'=>0,"msg"=>"活动结束时间不可早于当前时间，请重新输入",'code'=>400];
        }
        elseif($activityParams['activity_name'] == "")
        {
            $return  = ['result'=>0,"msg"=>"活动名称有误，请重新输入",'code'=>400];
        }
        elseif($activityId == 0)
        {
            $return  = ['result'=>0,"msg"=>"未指定活动，请重新输入",'code'=>400];
        }
        else
        {
            //检查对当前活动的权限
            $permission = (new ActivityService())->getUserActivityPermission($user_info->user_id,$activityId);
            if(!$permission)
            {
                $return  = ['result'=>0,"msg"=>"您没有执行此操作的权限哦",'code'=>403];
            }
            else
            {
                //检查名称是否重复
                $nameExists = $this->getActivityByName($activityParams['activity_name'],$activityParams['end_time'],$user_info->company_id);
                //存在且不是自己
                if(isset($nameExists->activity_id) && $nameExists->activity_id != $activityId)
                {
                    $return  = ['result'=>0,"msg"=>"活动名称已经存在了哦，请重新输入",'code'=>400];
                }
                else
                {
                    $activityParams['weekly_rebuild'] = ($activityParams['weekly_rebuild']>=0 && $activityParams['weekly_rebuild']<=6)?$activityParams['weekly_rebuild']:-1;
                    //写入数据
                    $activity = (new Activity())::findFirst(['activity_id='.$activityId]);
                    $activity->activity_name = $activityParams['activity_name'];
                    $activity->comment = $activityParams['comment'];
                    $activity->club_id = $activityParams['club_id'];
                    $activity->company_id = $user_info->company_id;
                    $activity->create_user_id = $user_info->user_id;
                    $activity->start_time = $activityParams['start_time'];
                    $activity->end_time = $activityParams['end_time'];
                    $activity->apply_start_time = $activityParams['apply_start_time'];
                    $activity->apply_end_time = $activityParams['apply_end_time'];
                    $activity->update_time = date("Y-m-d H:i:s",$currentTime);
                    $activity->club_member_only = $activityParams['club_member_only'];
                    $activity->member_limit = $activityParams['member_limit'];
                    $activity->icon = "";
                    $activity->status = 1;
                    $activity->detail = json_encode(
                        [
                            "checkin"=>$activityParams['checkin']??[],
                            "monthly_apply_limit"=>$activityParams['monthly_apply_limit'],
                            "weekly_rebuild"=>$activityParams['weekly_rebuild']??-1
                        ]
                    );
                    $update = $activity->save();
                    if ($update === false)
                    {
                        $return  = ['result'=>0,"msg"=>$this->msgList['activity_update_fail'],'code'=>400];
                    }
                    else
                    {
                        $activityInfo  = $this->getActivityInfo($activityId,'*',0);
                        $return  = ['result'=>1,"msg"=>$this->msgList["activity_update_success"],'data'=>$activityInfo,'code'=>200];
                    }
                }
            }
        }
        return $return;
    }
    //根据名称获取活动
    public function getActivityByName($activityName,$end_time = "",$company_id =0)
    {
        $params =             [
            "activity_name = '$activityName' and company_id = '$company_id' and end_time <='".$end_time."'",
            "columns" => "activity_id"
        ];
        return (new Activity())->findFirst($params);
    }
    public function getActivityInfo($activity_id,$columns = "activity_id,activity_name",$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->activity_info;
        $cacheName = $cacheSetting->name.$activity_id;
        $params =             [
            "activity_id = ".$activity_id,
            "columns" => '*',
        ];
        if($cache == 1)
        {
            $activityCache = $this->redis->get($cacheName);
            $activityCache = json_decode($activityCache);
            if(isset($activityCache->activity_id))
            {
                $activity = $activityCache;
            }
            else
            {
                $activity = (new \HJ\Activity())->findFirst($params);
                if(isset($activity->activity_id)) {
                    $this->redis->set($cacheName, json_encode($activity));
                    $this->redis->expire($cacheName, $cacheSetting->expire);
                    $activity = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    $activity = [];
                }
            }
        }
        else
        {
            $activity = (new \HJ\Activity())->findFirst($params);
            if(isset($activity->activity_id)) {
                $this->redis->set($cacheName, json_encode($activity));
                $this->redis->expire($cacheName, $cacheSetting->expire);
                $activity = json_decode($this->redis->get($cacheName));
            }
            else
            {
                $activity = [];
            }
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($activity as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($activity->$key);
                }
            }
        }
        return $activity;
    }
    public function getActivityByConnectedId($connected_id)
    {
        $params =             [
            "connect_activity_id = ".$connected_id,
            "columns" => '*',
        ];
        return (new \HJ\Activity())->findFirst($params);
    }

    /*
     *获取用户参加的活动列表
     */
    public function getActivityList($user_id,$order = 'id DESC'){
        $conditions = 'user_id ='.$user_id;
        $params = [
            $conditions,
            "order" => $order,
        ];
        $activityList = (new \HJ\UserActivityLog())->find($params);
        return $activityList;
    }

    /*
     * 获取用户管理的活动列表
     */
    public function getUserActivityListWithPermission($user_id,$club_id,$columns,$start = 0,$page=1,$pageSize =4){
        //查询用户是否有超级管理员权限
        $user_info = (new UserService())->getUserInfo($user_id,'user_id,manager_id,company_id');
        if(isset($user_info->manager_id)&&$user_info->manager_id!=0)
        {
            $activity_list = $this->getActivityListByCompany($user_info->company_id,$columns,$club_id,0);
        }
        else
        {
            $activity_list = [];
        }
        foreach($activity_list as $key => $activityInfo)
        {
            if(!$activityInfo || $activityInfo->club_id == 0 || $activityInfo->status!=1 )
            {
                unset($activity_list[$key]);
            }
        }
        $activity_list = array_combine(array_column($activity_list,"activity_id"),array_values($activity_list));
        $created_activity_list = $this->getActivityListByCreater($user_info->company_id,$user_info->user_id,$columns,$club_id,0);
        foreach($created_activity_list as $key => $created)
        {
            if(!$created || $created->club_id == 0 || $created->status!=1 )
            {
                //unset($created_activity_list[$key]);
                //continue;
            }else {
                if(!isset($activity_list[$created->activity_id]))
                {
                    $activity_list[$created->activity_id] = $created;
                }
/*
                $flag = 0;

                foreach ($activity_list as $key2 => $manager) {
                    if (!$manager) {
                        continue;
                    }
                    if ($created->activity_id == $manager->activity_id) {
                        $flag = 1;
                        break;
                    }
                }
                if ($flag == 1) {
                    continue;
                }
                $activity_list[] = $created;
*/            }
        }
        if($start>0)
        {
            $residuals = 1;
            if(($start+$pageSize)>=count($activity_list))
            { //分页结束
                $residuals = 0;
            }
            $activity_list = array_slice($activity_list,$start,$pageSize,true);
            $return = ['residuals'=>$residuals,'activity_list'=>$activity_list];
        }else
        {

            $offset = ($page - 1)*$pageSize;
            $residuals = 1;
            if(($offset+$pageSize)>=count($activity_list))
            {
                $residuals = 0;
            }
            $activity_list = array_slice($activity_list,$offset,$pageSize,true);
            $return = ['residuals'=>$residuals,'activity_list'=>$activity_list];
        }
        return $return;

    }



    //活动报名方法
    public function activityApply($activity_id,$user_id=0)
    {
        $common = new Common();
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        if(empty($activity_id)){
            $return['msg']  = $this->msgList['activity_empty'];
        }else{
            //查询活动数据
            $activityInfo = $this->getActivityInfo($activity_id,'*');
            $member_count = (new ActivityService())->getActivityMemberCount($activity_id);
            //检测是否是俱乐部成员
            $is_member = (new ClubService())->checkUserIsClubMember($user_id,$activityInfo->club_id);
            if(!isset($activityInfo->activity_id)){
                $return['msg']  = $this->msgList['activity_empty'];
            }
            else if($activityInfo->status!=1){
                $return['msg']  = $this->msgList['activity_cancelled'];
            }
            else if(time()<strtotime($activityInfo->apply_start_time)){
                $return['msg']  = $this->msgList['activity_apply_not_started'];
            }else if(time()>strtotime($activityInfo->end_time)){
                $return['msg']  = $this->msgList['activity_ended'];
            }else if(time()<strtotime($activityInfo->apply_start_time) || time()>strtotime($activityInfo->apply_end_time)){
                $return['msg']  = $this->msgList['activity_expire'];
            }else if($member_count>=$activityInfo->member_limit){
                $return['msg']  = $this->msgList['activity_member_limit'];
                $rerurn['code'] = $this->config->special_code['activity_member_full'];
            }else if($activityInfo->club_member_only&&$is_member == 0){
                    $club_info = (new ClubService())->getClubInfo($activityInfo->club_id,'club_id,club_name');
                    $return['msg']  = $this->msgList['activity_club_limit'].$club_info->club_name;
                    $return['code'] = $this->config->special_code['need_club_membership'];
                    $return['data']['club_id'] = $club_info->club_id;
            }else{
                $activityLog = $this->getActivityLogByUser($user_id,$activity_id);
                if(isset($activityLog->id)){
                    $return  = ['result'=>1, 'msg'=>$this->msgList['activity_apply_success'], 'code'=>200, 'data'=>$activityLog];
                }else{
                    $userInfo = (new UserService())->getUserInfo($user_id,"user_id,nick_name,true_name,mobile",1);
                    //添加记录
                    $useractivitylog = new \HJ\UserActivityLog();
                    $useractivitylog->user_id = $user_id;
                    $useractivitylog->activity_id = $activity_id;
                    $useractivitylog->user_name = $userInfo->true_name;
                    $useractivitylog->mobile = $userInfo->mobile??"";
                    $useractivitylog->department = "";
                    $useractivitylog->checkin_status = 0;
                    $useractivitylog->create_time = date("Y-m-d H:i:s");
                    $useractivitylog->update_time = date("Y-m-d H:i:s");
                    if ($useractivitylog->create() === false) {
                        $return['msg']  = $this->msgList['activity_apply_fail'];
                    }else{
                        $return  = ['result'=>1, 'msg'=>$this->msgList['activity_apply_success'], 'code'=>200, 'data'=>$useractivitylog];
                    }
                }
            }
        }
        return $return;
    }
    public function getActivityLogByUser($user_id,$activity_id)
    {
        return  \HJ\UserActivityLog::findFirst([
            "activity_id=:activity_id: and user_id=:user_id:",
            'bind'=>['activity_id'=>$activity_id, 'user_id'=> $user_id] ,
            'order'=>'id desc'
        ]);
    }
    //获取企业下的所有活动列表
    public function getActivityListByCompany($company_id,$columns = 'activity_id,activity_name',$club_id = -1,$cache = 1)
    {
        $conditions = "company_id='".$company_id."'";

        if($club_id != -1)
        {
            $conditions .= "and 'club_id' = ".$club_id;
        }
        $cacheSetting = $this->config->cache_settings->activity_list_by_company;
        $cacheName = $cacheSetting->name.$company_id;
        $params = [
            $conditions,
            'columns'=>'activity_id',
            'order' => 'activity_id DESC'
        ];
        if($cache == 0)
        {
            //获取活动列表
            $activityList = \HJ\Activity::find($params)->toArray();
            if($activityList)
            {
                $this->redis->set($cacheName,json_encode($activityList));
                $this->redis->expire($cacheName,$cacheSetting->expire);
                $activityList = json_decode($this->redis->get($cacheName));
            }
            else
            {
                $activityList = [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if($cache)
            {
                $activityList = $cache;
            }
            else
            {
                //获取活动列表
                $activityList = \HJ\Activity::find($params);
                if($activityList)
                {
                    $this->redis->set($cacheName,json_encode($activityList));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                    $activityList = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    $activityList = [];
                }
            }
        }
        foreach($activityList as $key => $value)
        {
            $activityInfo = $this->getActivityInfo($value->activity_id,$columns);
            if(!$activityInfo || $activityInfo->status!=1)
            {
                unset($activityList[$key]);
            }else{
                $activityList[$key] = $activityInfo;
            }
        }
        return $activityList;
    }
    //获取企业下的特定用户创建的所有活动列表
    public function getActivityListByCreater($company_id,$create_user_id,$columns = 'activity_id,activity_name',$club_id= -1,$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->activity_list_by_company;
        $cacheName = $cacheSetting->name.$company_id;
        $conditions = "company_id='".$company_id."' and create_user_id = '".$create_user_id."'";
        if($club_id != -1)
        {
            $conditions .= " and club_id = ".$club_id;
        }
       $params =             [
            $conditions,
            'columns'=>'activity_id',
            'order' => 'activity_id DESC'
        ];
        if($cache == 0)
        {
            //获取活动列表
            $activityList = \HJ\Activity::find($params);
            if($activityList)
            {
                $this->redis->set($cacheName,json_encode($activityList));
                $this->redis->expire($cacheName,$cacheSetting->expire);
                $activityList = json_decode($this->redis->get($cacheName));
            }
            else
            {
                $activityList = [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if($cache)
            {
                $activityList = $cache;
            }
            else
            {
                //获取活动列表
                $activityList = \HJ\Activity::find($params);
                if($activityList)
                {
                    $this->redis->set($cacheName,json_encode($activityList));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                    $activityList = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    $activityList = [];
                }
            }
        }
        foreach($activityList as $key => $value)
        {
                $activityInfo = $this->getActivityInfo($value->activity_id, $columns);
                if(!$activityInfo || $activityInfo->status!=1)
                {
                    unset($activityList[$key]);
                }else
                {
                    $activityList[$key] = $activityInfo;
                }
        }
        return $activityList;
    }
    //获取用户最近创建的活动关联的签到地址
    public function getPositionListByCreater($company_id = 0,$create_user_id = 0)
    {
        $userCreatedAcitvityList = $this->getActivityListByCreater($company_id,$create_user_id,"activity_id,detail,status");
        $positionList = [];
        foreach($userCreatedAcitvityList as $key => $activityDetail)
        {
            if(!$activityDetail)
            {
                continue;
            }
            $activityDetail->detail = json_decode($activityDetail->detail);
            $checkin = $activityDetail->detail->checkin??[];
            if(isset($checkin->address))
            {
                $m = md5(json_encode($checkin));
                if(!isset($positionList[$m]))
                {
                    $positionList[$m] = $checkin;
                }
            }
        }
        return $positionList;
    }
    //检查位置是否满足活动签到的条件
    public function checkPostitionForCheckin($activity_id,$user_id,$position = [])
    {
        $activityInfo = $this->getActivityInfo($activity_id,"*");
        if(isset($activityInfo->activity_id))
        {
            $detail = json_decode($activityInfo->detail,true);
            $distance = Common::getDistance($position['latitude'],$position['longitude'],$detail['checkin']['latitude'],$detail['checkin']['longitude']);
            if($distance <= $this->config->checkin_max_distance)
            {
                $return  = ['result'=>1,"msg"=>"可以签到",'data'=>$distance,'code'=>200];
            }
            else
            {
                $return  = ['result'=>0,"msg"=>$this->msgList["checkin_over_distance"],'code'=>400];
            }
        }
        else
        {
            $return  = ['result'=>0,"msg"=>$this->msgList["activity_empty"],'code'=>400];
        }

        return $return;
    }
    //活动签到
    public function activityCheckin($activity_id,$user_id,$position = [])
    {
        $activityInfo = $this->getActivityInfo($activity_id,"*");
        if(isset($activityInfo->activity_id))
        {
            if($activityInfo->status==1)
            {
                $currentTime = time();
                if((strtotime($activityInfo->apply_start_time)<=$currentTime) && (strtotime($activityInfo->apply_end_time)>=$currentTime))
                {
                    $detail = json_decode($activityInfo->detail,true);
                    $distance = Common::getDistance($position['latitude'],$position['longitude'],$detail['checkin']['latitude'],$detail['checkin']['longitude']);
                    //校验距离
                    if($distance <= $this->config->checkin_max_distance)
                    {
                        //获取报名记录
                        $activityLog = $this->getActivityLogByUser($user_id,$activity_id);
                        //如果找到
                        if(isset($activityLog->id))
                        {
                            //已经签到过了
                            if($activityLog->checkin_status==1)
                            {
                                $return  = ['result'=>1,"msg"=>$this->msgList['activity_checkin_success'],'data'=>[],'code'=>200];
                            }
                            else
                            {
                                $data = ["checkin_status"=>1];
                                $update = $this->updateActivityLog($activityLog->id,$data);
                                if($update)
                                {
                                    $return  = ['result'=>1,"msg"=>$this->msgList['activity_checkin_success'],'data'=>[],'code'=>200];
                                }
                                else
                                {
                                    $return  = ['result'=>0,"msg"=>$this->msgList['activity_checkin_fail'],'data'=>[],'code'=>200];
                                }
                            }
                        }
                        else
                        {
                            $return  = ['result'=>0,"msg"=>$this->msgList["activity_log_not_found"],'code'=>400];
                        }
                    }
                    else
                    {
                        $return  = ['result'=>0,"msg"=>$this->msgList["checkin_over_distance"],'code'=>400];
                    }
                }
                else
                {
                    $return  = ['result'=>0,"msg"=>$this->msgList['activity_expire'],'code'=>400];
                }
            }
            else
            {
                $return  = ['result'=>0,"msg"=>$this->msgList["activity_cancelled"],'code'=>400];
            }
        }
        else
        {
            $return  = ['result'=>0,"msg"=>$this->msgList["activity_empty"],'code'=>400];
        }


        return $return;
    }

    /*
     * 活动人数
     */
    public function getActivityMemberCount($activity_id){
        $count = (new \HJ\UserActivityLog())->findFirst(['activity_id ='.$activity_id,'columns'=>'count(activity_id)']);
        $count = $count->{0};
        return $count;
    }

    /*
     * 活动成员列表
     */
    public function getActivityMemberList($activity_log){
        $params = [
            'activity_id ='.$activity_log,
            'columns'=>'user_id'
        ];
        $member_list = (new \HJ\UserActivityLog())->find($params);
        return $member_list;
    }

    /*
     * 查询用户活动权限
     */
    public function getUserActivityPermission($user_id,$activity_id){
        $user_info = (new UserService())->getUserInfo($user_id,'*',0);
        if(isset($user_info->manager_id)&&$user_info->manager_id>0)
        {
          return 1;
        }else
        {
            $params = [
                'activity_id ='.$activity_id,
                'columns'=>'activity_id,create_user_id'
            ];
            $activityInfo = (new Activity())->findFirst($params);
            if(isset($activityInfo->create_user_id) && $activityInfo->create_user_id == $user_id)
            {
                return 1;
            }
        }
         return 0;
    }


    /*
     * 取消活动
     */
    public function activityCancel($user_id,$activity_id){
        //查询用户权限
        $permission = $this->getUserActivityPermission($user_id,$activity_id);
        if(!$permission) {
            $return = ['result' => 0, "msg" => "您没有此活动的权限", 'code' => 400];
            return $return;
        }
            $activityInfo = (new ActivityService())->getActivityInfo($activity_id,'activity_id,connect_activity_id,status,detail',"*",1);
            if(!$activityInfo || $activityInfo->status!=1)
            {
                $return  = ['result'=>1,"msg"=>"取消成功",'code'=>200];
                return $return;
            }
            $detail = json_decode($activityInfo->detail,true);
            $detail['cancel_info']['cancel_user_id'] = $user_id;
            $detail['cancel_info']['cancel_time'] = date('Y-m-d h:i',time());
            $map['detail'] = json_encode($detail);
            $map['status'] = 0;
            $res = $this->updateActivityInfo($map,$activity_id);
            if($res['result'])
            {
                $connected = $this->getActivityByConnectedId($activity_id);
                if(isset($connected->activity_id))
                {
                    $map = $connected->toArray();
                    $map['connect_activity_id'] = $activityInfo->connect_activity_id;
                    $this->updateActivityInfo($map,$connected->activity_id);
                }
                $return  = ['result'=>1,"msg"=>"取消成功",'code'=>400];
            }else
            {
                $return  = ['result'=>0,"msg"=>"取消失败",'code'=>400];
            }
            return $return;
    }
    //更新用户信息
    public function updateActivityInfo($map,$activity_id=0)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        //修改用户信息
        $activityInfo = \HJ\Activity::findFirst(["activity_id =".$activity_id]);
        foreach($map as $key => $value)
        {
            $activityInfo->$key = $value;
        }
        $activityInfo->update_time = date("Y-m-d H:i:s");
        if ($activityInfo->update() === false) {
            $return['msg']  = '修改失败';
        }else {
            $activityInfo = $this->getActivityInfo($activityInfo->activity_id,'*',0);
            if($activityInfo->club_id>0)
            {
                (new ActivityService())->getActivityListByCompany($activityInfo->company_id,'*',$activityInfo->club_id,0);
            }
            (new ActivityService())->getActivityListByCreater($activityInfo->company_id,$activityInfo->create_user_id,'*',$activityInfo->club_id,0);
            $return = ['result' => 1, 'msg' => '修改成功', 'code' => 200, 'data' => []];
        }
        return $return;
    }

    //更新报名记录
    public function updateActivityLog($id=0,$map)
    {
        //修改用户信息
        $activity_log = \HJ\UserActivityLog::findFirst(["id = '".$id."'"]);
        foreach($map as $key => $value)
        {
            if(!empty($value))
            {
                $activity_log->$key = $value;
            }
        }
        $activity_log->last_update_time = date("Y-m-d H:i:s");
        if ($activity_log->update() === false) {
            $return['msg']  = "更新失败";
        }else {
            $return = ['result' => 1, 'msg' => "更新成功", 'code' => 200, 'data' => []];
        }
        return $return;
    }

    /*
     * 获取某公司活动列表
     */
    public function getMonthlyActivityList($company_id,$month){
        $year = date('Y',time());
        $date =$year.'-'.$month;
        $monthly_activities = [];
        $date_list = []; //日期下标数据
        $activity_list = (new \HJ\Activity())->find(['company_id = '.$company_id,'columns'=>'activity_id',"order"=>"apply_start_time"]);
        foreach ($activity_list as $key=>$activity_info) {
            $activity_info = $this->getActivityInfo($activity_info->activity_id, "activity_id,status,club_id,start_time,end_time,icon,comment");
            $activity_info = json_decode(json_encode($activity_info), true);
            if (isset($activity_info->activity_id) && $activity_info->status == 1)
            {
                $activity_start_time = '';
                $activity_start_time = date('Y-m',strtotime($activity_info->start_time));
                if($activity_start_time!=$date)
                {
                    continue;
                }
                $activity_data = [];
                $day = date('d',strtotime($activity_info->start_time));
                $activity_data['activity_id'] = $activity_info->activity_id;
                $activity_data['date'] = $day;
                $monthly_activities[] = $activity_data;
                $activity_data = [];
                $activity_data['activity_id'] = $activity_info->activity_id;
                $activity_data['comment'] = $activity_info->comment;
                $activity_data['time'] = date('h:i',strtotime($activity_info->start_time));
                $activity_data['club_icon'] = '';
                $activity_data['club_name'] = '';
                if($activity_info->club_id>0)
                {
                    $club_info = (new ClubService())->getClubInfo($activity_info->club_id,'club_id,icon,club_name');
                    $activity_data['club_icon'] = $club_info->icon;
                    $activity_data['club_name'] = $club_info->club_name;
                }
                $date_list[$day] = $activity_data;
            }
        }
        return ['month_activities'=>$monthly_activities,'date_data'=>$date_list];
    }
    /*
     * 获取活动已签到人数
     */
    public function getActivityCheckinCount($activity_id){
        $activity_count = (new \HJ\UserActivityLog())->findFirst(['activity_id ='.$activity_id.' and checkin_status = 1','columns'=>'count(activity_id)']);
        return $activity_count->{0};
    }









































}