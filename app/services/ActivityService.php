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
        "activity_apply_success"=>"报名成功！",
        "activity_apply_error"=>"报名失败！",
    ];

    
    public function createActivity($activityParams = [],$user_info)
    {
        $currentTime = time();
        //活动时间校验
        if($activityParams['start_time']=="" || $activityParams['end_time']=="")
        {
            $return  = ['result'=>0,"msg"=>"活动时间有误，请重新输入",'code'=>400];
        }
        //活动名称长度校验
        elseif(strlen($activityParams['activity_name'])>=32)
        {
            $return  = ['result'=>0,"msg"=>"活动名称超长，请重新输入",'code'=>400];
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
                //检查名称是否重复
                $nameExists = $this->getActivityByName($activityParams['activity_name'],$activityParams['end_time'],$user_info->company_id);
                if(isset($nameExists->activity_id))
                {
                    $return  = ['result'=>0,"msg"=>"活动名称已经存在了哦，请重新输入",'code'=>400];
                }
                else
                {
                    $activityParams['weekly_rebuild'] = ($activityParams['weekly_rebuild']>=0 && $activityParams['weekly_rebuild']<=6)?$activityParams['weekly_rebuild']:-1;
                    //写入数据
                    $activity = new Activity();
                    $activity->activity_name = $activityParams['activity_name'];
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
                        $return  = ['result'=>1,"msg"=>"活动创建成功！",'data'=>$this->getActivityInfo($activity->activity_id),'code'=>200];
                    }
                }
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
            //检查对当前俱乐部的权限
            $permission = (new ClubService())->getUserClubPermission($user_info->user_id,$activityParams['club_id'],1);
            if(intval($permission) == 0)
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
                        $return  = ['result'=>0,"msg"=>"活动更新失败，请稍后再试",'code'=>400];
                    }
                    else
                    {
                        $return  = ['result'=>1,"msg"=>"活动更新成功！",'data'=>$this->getActivityInfo($activity->activity_id),'code'=>200];
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

    /*
     *获取用户参加的活动列表
     */
    public function getActivityList($user_id,$start = 0,$page = 1,$pageSize = 3,$order = 'id DESC'){
        $conditions = 'user_id ='.$user_id;
        if($start>0)
        {
            $conditions .= 'id >'.$start;
        }
        $params = [
            $conditions,
            "order" => $order,
            "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
        ];
        $activityList = (new \HJ\UserActivityLog())->find($params);
        return $activityList;
    }

    /*
     * 获取用户管理的活动列表
     */
    public function getUserActivityListWithPermission($user_id,$club_id){
        //查询用户是否有超级管理员权限
        $user_info = (new UserService())->getUserInfo($user_id,'user_id,manager_id,company_id');
        if(isset($user_info->manager_id)&&$user_info->manager_id!=0)
        {

            $activity_list = $this->getActivityListByCompany($user_info->company_id,"activity_id,activity_name",$club_id,0);
        }
        else
        {
            $activity_list = [];
        }
        $created_activity_list = $this->getActivityListByCreater($user_info->company_id,$user_info->user_id,"activity_id,activity_name,club_id,start_time",$club_id,0);
      //  print_r($activity_list);die();
       // print_r($created_activity_list);
        foreach($created_activity_list as $key => $created)
        {
            $flag = 0;
            foreach($activity_list as $key2 => $manager)
            {
                if($created->activity_id == $manager->activity_id)
                {
                    $flag = 1;
                    break;
                }
            }
            if($flag == 1)
            {
                 continue;
            }
                $activity_list[] = $created;
        }
        return $activity_list;

    }

    /*
     * 获取活动参加人数
     */
    public function getActivityMemberCount($activity_id){
        $count =  (new \HJ\UserActivityLog())->query()->where('activity_id ='.$activity_id)->execute()->count();
        return $count;
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
            if(!isset($activityInfo->activity_id)){
                $return['msg']  = $this->msgList['activity_empty'];
            }else if(time()<strtotime($activityInfo->apply_start_time)){
                $return['msg']  = $this->msgList['activity_apply_not_started'];
            }else if(time()>strtotime($activityInfo->end_time)){
                $return['msg']  = $this->msgList['activity_ended'];
            }else if(time()<strtotime($activityInfo->apply_start_time) || time()>strtotime($activityInfo->apply_end_time)){
                $return['msg']  = $this->msgList['activity_expire'];
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
                    $useractivitylog->create_time = date("Y-m-d H:i:s");
                    if ($useractivitylog->create() === false) {
                        $return['msg']  = $this->msgList['activity_apply_error'];
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
    public function getActivityListByCompany($company_id,$columns = 'activity_id,activity_name',$club_id,$cache = 1)
    {
        $conditions = "company_id='".$company_id."'";

        if(strlen($club_id)!=0)
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
            $activityInfo = $this->getActivityInfo($value->activity_id,$columns);
            $activityList[$key] = $activityInfo;
        }
        return $activityList;
    }
    //获取企业下的特定用户创建的所有活动列表
    public function getActivityListByCreater($company_id,$create_user_id,$columns = 'activity_id,activity_name',$club_id='',$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->activity_list_by_company;
        $cacheName = $cacheSetting->name.$company_id;
        $conditions = "company_id='".$company_id."' and create_user_id = '".$create_user_id."'";
        if(strlen($club_id)!=0)
        {
            $conditions .= "and club_id = ".$club_id;
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
            $activityInfo = $this->getActivityInfo($value->activity_id,$columns);
            $activityList[$key] = $activityInfo;
        }
        return $activityList;
    }
    //获取用户最近创建的活动关联的签到地址
    public function getPositionListByCreater($company_id = 0,$create_user_id = 0)
    {
        $userCreatedAcitvityList = $this->getActivityListByCreater($company_id,$create_user_id,"activity_id,detail");
        $positionList = [];
        foreach($userCreatedAcitvityList as $key => $activityDetail)
        {
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

    public function checkPostitionForCheckin($activity_id,$user_id,$position = [])
    {
        $activityInfo = $this->getActivityInfo($activity_id,"*");
        $detail = json_decode($activityInfo->detail,true);
        $distance = Common::getDistance($position['latitude'],$position['longitude'],$detail['checkin']['latitude'],$detail['checkin']['longitude']);
        return $distance;
    }
}