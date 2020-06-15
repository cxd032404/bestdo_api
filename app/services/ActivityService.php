<?php
use HJ\Activity;
use HJ\UserInfo;
class ActivityService extends BaseService
{
	private $msg = 'success';

    
    public function createActivity($activityParams = [],$user_info)
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
        else
        {
            //检查对当前俱乐部的权限
            $permission = (new ClubService())->getUserClubPermission($user_info->user_id,$activityParams['club_id'],1);
            if($permission == 0)
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
                    $activity->club_id = $activityParams['club_id'];
                    $activity->company_id = $user_info->company_id;
                    $activity->start_time = $activityParams['start_time'];
                    $activity->end_time = $activityParams['end_time'];
                    $activity->apply_start_time = $activityParams['apply_start_time'];
                    $activity->apply_end_time = $activityParams['apply_end_time'];
                    $activity->create_time = $activity->update_time = date("Y-m-d H:i:s",$currentTime);
                    $activity->club_member_only = $activityParams['club_member_only'];
                    $activity->member_limit = $activityParams['member_limit'];
                    $activity->icon = "";
                    $activity->activity_sign = "";
                    $activity->detail = "";
                    //print_R($activity);
                    /*
                    $activity->detail = json_encode(
                        [
                            "checkin"=>[],
                            "monthly_apply_limit"=>$activityParams['monthly_apply_limit'],
                            "weekly_rebuild"=>$activityParams['weekly_rebuild']??-1
                            ]
                    );
                    */
                    $create = $activity->save();
                    echo "create:";
                    var_dump($create);
                    die();
                    if ($create === false)
                    {
                        $return  = ['result'=>0,"msg"=>"活动创建失败，请稍后再试",'code'=>400];
                    }
                    else
                    {
                        $return  = ['result'=>1,"msg"=>"活动创建成功！",'data'=>$this->getActivityInfo($activity->id),'code'=>200];
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
            "activity_name = '$activityName' and company_id = '$company_id' and end_time <".$end_time,
            "columns" => "activity_id"
        ];
        return (new Activity())->findFirst($params);
    }
    public function getActivityInfo($activity_id,$columns = "activity_id,acitivity_name",$cache = 1)
    {
        $cacheSettings = $this->config->cache_settings->activity_info;
        $cacheName = $cacheSettings->name.$activity_id;
        $params =             [
            "activity_id = ".$activity_id,
            "columns" => '*',
        ];
        if($cache == 1)
        {
            $postsCache = $this->redis->get($cacheName);
            $postsCache = json_decode($postsCache);
            if(isset($postsCache->post_id))
            {
                $posts = $postsCache;
            }
            else
            {
                $activity = (new \HJ\Activity())->findFirst($params);
                if(isset($activity->activity_id)) {
                    $this->redis->set($cacheName, json_encode($activity));
                    $this->redis->expire($cacheName, $cacheSettings->expire);
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
                $this->redis->expire($cacheName, $cacheSettings->expire);
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
}