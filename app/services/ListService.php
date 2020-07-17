<?php
use HJ\ListModel;
class ListService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListInfo($list_id,$columns = "list_id,company_id,detail",$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->list;
        $cacheName = $cacheSetting->name.$list_id;
        if($cache == 1)
        {
            $cacheData = $this->redis->get($cacheName);
            $listData = json_decode($cacheData);
            if(isset($cacheData->list_id))
            {
                //$return = $cacheData;
            }else
            {
                $listData = (new ListModel())->findFirst(
                    [
                        "list_id = $list_id",
                        "columns" => '*'
                    ]);
                $this->redis->set($cacheName,json_encode($listData));
                $this->redis->expire($cacheName,$cacheSetting->expire);
                $listData = json_decode($this->redis->get($cacheName));
            }
        }else
        {
            $listData = (new ListModel())->findFirst(
                [
                    "list_id = $list_id",
                    "columns" => '*'
                ]);
            $this->redis->set($cacheName,json_encode($listData));
            $this->redis->expire($cacheName,$cacheSetting->expire);
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($listData as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($listData->$key);
                }
            }
        }
        return $listData;
    }


    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListByActivity($activity_id,$columns = "list_id,company_id,detail")
    {
        return (new ListModel())->find(
            [
                "activity_id = $activity_id",
                "columns" => $columns
            ]);
    }

    public function processAfterPostAction($list_id,$user_id,$detail)
    {
        $return = ['after_url'=>'','params'=>[]];
        if(isset($detail['after_action'])) {
            $detail['after_action'] = $detail['after_action']??"";
        }if(isset($detail['after_url']))
        {
            $detail['after_url'] = str_replace("#list_id#",$list_id,($detail['after_url']??""));
            $detail['after_url'] = str_replace("#user_id#",$user_id,($detail['after_url']??""));
            $t = explode("|",$detail['after_url']??"");

            foreach($t as $key => $value)
            {
                if($key==0)
                {
                    $return['after_url'] = trim($value);
                }
                else
                {
                    $t2 = explode("=",trim($value));
                    $return['params'][] = ["key"=>trim($t2[0]),'value'=>trim($t2[1]??"")];
                }
            }
        }
        return $return;
    }
    //检查用户对特定列表是否有提交权限
    public function checkUserListPermission($user_id,$list_id)
    {
        //获取列表信息
        $listInfo = $this->getListInfo($list_id,"list_id,activity_id,company_id,detail");
        $listInfo->detail = json_decode($listInfo->detail);
        $userInfo  = (new UserService())->getUserInfo($user_id,"user_id,company_id,manager_id");
        $listInfo->detail->manager_only = $listInfo->detail->manager_only??0;
        if($listInfo->company_id == $userInfo->company_id)
        {
            //如果关联活动
            if($listInfo->activity_id>0)
            {
                //管理员不限定
                if($userInfo->manager_id>0)
                {

                }
                else
                {
                    //检查报名报名记录
                    $activityLog = (new ActivityService())->getActivityLogByUser($user_id,$listInfo->activity_id);
                    //已报名
                    if(!$activityLog)
                    {

                    }
                    else
                    {
                        $return = ['result' => 0, "msg" => "需要报名活动才能提交", 'code' => 403];
                    }
                }
            }
            if(!isset($return))
            {
                //只有管理员可以提交
                if($listInfo->detail->manager_only==1)
                {
                    if($userInfo->manager_id>0)
                    {
                        $return = ['result'=>1,'code'=>200];
                    }
                    else
                    {
                        $return = ['result' => 0, "msg" => "只有管理员可以提交", 'code' => 403];
                    }
                }
                else
                {
                    $postExists = (new PostsService())->getPostsList($list_id,[$user_id??0],"post_id","post_id DESC",0,1,1,0);
                    //已经提交过
                    if(count($postExists['data'])>0)
                    {
                        $return = ['result' => 0, "msg" => "列表只能提交一次", 'code' => 403];
                    }
                    else
                    {
                        $return = ['result'=>1,'code'=>200];
                    }
                }
            }
        }
        else
        {
            $return = ['result' => 0, "msg" => "您没有执行对该列表的权限", 'code' => 403];
        }
        return $return;

    }
    //根据company_id获取列表
    public function getListByCompany($company_id,$columns = 'list_id,list_name,list_type'){
        $list = (new Hj\ListModel())->find(['company_id ='.$company_id,'columns'=>$columns]);
        return $list ;

    }
}