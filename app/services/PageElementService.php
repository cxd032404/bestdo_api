<?php


class PageElementService extends BaseService
{
    private $weekarray=array("日","一","二","三","四","五","六");

    /*
     * 列表
     * userinfo 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */
    public function getElementPage_list($data,$params,$user_info,$company_id){
        //指定数据
        if(isset($data['detail']['list_id']))
        {
            $list_id = $data['detail']['list_id'];
        }
        else//页面获取
        {
            $list_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        //获取列表
        $listInfo = (new ListService())->getListInfo($list_id,"list_id,list_type");
        //获取符合查询条件用户
        $search_content = $this->getFromParams($params,"search_content","");
        $userList = [];
        if($search_content){
            $userList = \HJ\UserInfo::find([
                "nick_name like '%".$search_content."%' and is_del=0 and company_id='".$company_id."'",
                'columns'=>'user_id',
                'order'=>'user_id desc'
            ])->toArray();
            $userList = array_column($userList,'user_id');
        }
        $data['data'] = (new PostsService())->getPostsList($listInfo->list_id,$userList,"*","post_id DESC",$this->getFromParams($params,"start",0),$this->getFromParams($params,"page",1),$this->getFromParams($params,"page_size",3));
        foreach($data['data']['data'] as $k => $postDetail)
        {
            $data['data']['data'][$k]->source = json_decode($postDetail->source,true);
            $data['data']['data'][$k]->source = (new UploadService())->parthSource($data['data']['data'][$k]->source);
            $data['data']['data'][$k]->source[0]['post_id'] = $postDetail->post_id;
            $data['data']['data'][$k]->source[0]['title'] = $postDetail->title;
            $data['data']['data'][$k]->list_type = $listInfo->list_type;
            $data['data']['data'][$k]->content = htmlspecialchars_decode($postDetail->content);
            $t = json_decode(json_encode($data['data']['data'][$k]->user_info),true);
            unset($data['data']['data'][$k]->user_info);
            foreach ($t as $userkey=>$value)
            {
                $data['data']['data'][$k]->$userkey = $value;
            }
            $postskudos_info = (new PostsService())->checkKudos($user_info['data']['user_id']??0,"",$data['data']['data'][$k]->post_id);
            $data['data']['data'][$k]->is_kudos = 0;
            if(isset($postskudos_info->id)){
                $data['data']['data'][$k]->is_kudos = 1;
            }
        }
        return $data;

    }
    /*
        * 报名记录
        * userinfo 用户信息
        * company_id 公司id
        * data 用户包含的element信息
        * params 页面标识和company_id
        */
    public function getElementPage_activityLog($data,$params,$user_info){
        $post_id = $this->getFromParams($params,"post_id","");
        if($post_id){
            $post_list = $post_id;
        }else{
            $post_list = [];
            $activity_log = \HJ\UserActivityLog::find([
                "user_id='".$user_info['data']['user_id']."'",
                "columns"=>"activity_id",
                "group"=>"activity_id"
            ])->toArray();
            foreach($activity_log as $k1=>$v1){
                $list = \HJ\ListModel::find(["activity_id='".$v1['activity_id']."'",])->toArray();
                foreach($list as $k2=>$v2){
                    $posts = \HJ\Posts::findFirst([
                        "list_id='".$v2['list_id']."' and user_id='".$user_info['data']['user_id']."' and visible=1 ",
                        "columns"=>"post_id", "group"=>"post_id", "order"=>"post_id desc"
                    ]);
                    if($posts){
                        $post_list[] = $posts['post_id'];
                    }
                }
            }
        }
        $data['data'] = (new UserService())->getPostByActivityAction($post_list,$this->getFromParams($params,"page",1),$this->getFromParams($params,"page_size",1));
        foreach($data['data']['data'] as $post_key=>$log)
        {
            $postskudos_info = (new PostsService())->checkKudos($user_info['data']['user_id']??0,"",$log['post_id']);
            $data['data']['data'][$post_key]['is_kudos'] = 0;
            if(isset($postskudos_info->id)){
                $data['data']['data'][$post_key]['is_kudos'] = 1;
            }
        }
        return $data;
    }
    /*
     * userinfo 滑动导航
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */
    public function getElementPage_slideNavi($data,$params,$user_info,$company_id){
        if($data['detail']['source_from']=="from_vote")
        {
            $voteInfo = (new VoteService())->getVote($data['detail']['vote_id'])->toArray();
            $voteInfo['detail'] = json_decode($voteInfo['detail'],true);
            $data['detail']['vote_option'] = $voteInfo['detail'];
        }
        if(isset($data['detail']['jump_urls']))
        {
            //滑动导航数据转化
            $navList = [];
            foreach ($data['detail']['jump_urls'] as $navkey=>$nacvalue) {
                $data['name'] = $navkey;
                $nav_type = explode('#',str_replace('"', '', $nacvalue));
                $data['url'] = reset($nav_type);
                $data['type'] = explode('.',str_replace('"', '', end($nav_type)));
                $navList[] = $data;
            }
            $data['detail']['jump_urls'] = $navList;
        }
        return $data;

    }
    /*
    * 企业信息
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */

    public function getElementPage_companyInfo($data,$params,$user_info,$company_id){
        $data['detail'] = (new CompanyService())->getCompanyInfo($company_id)->toArray();
        return $data;
    }
    /*
    * 用户信息
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */

    public function getElementPage_userInfo($data,$params,$user_info,$company_id){
        $data['detail']['user_info'] = $user_info;
        return $data;
    }
    /*
    * 提交
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_post($data,$params,$user_info,$company_id){
        $data['detail']['available'] = 1;

        if(isset($data['detail']['list_id']))
        {
            $list_id = $data['detail']['list_id'];
        }
        else
        {
            $list_id = $this->getFromParams($params,$dat['detail']['from_params'],0);
        }
        $data['detail']['available'] = 1;
        //获取列表信息
        $listInfo = (new ListService())->getListInfo($list_id,"list_id,activity_id,detail");
        if($listInfo->activity_id>0)
        {
            $activityLog = (new ActivityService())->getActivityLogByUser($user_info['data']['user_id'],$listInfo->activity_id);
            if(!$activityLog)
            {
                $data['detail']['available'] = 0;
            }
        }
        if($data['detail']['available'] == 1)
        {
            //数据解包
            $listInfo->detail = json_decode($listInfo->detail,true);
            $user_id = isset($user_info['data']['user_id'])?[$user_info['data']['user_id']]:[];
            $postExists = (new PostsService())->getPostsList($list_id,$user_id??0,"post_id","post_id DESC",0,1,1);
            //已经提交过
            if(count($postExists['data'])>0)
            {
                $data['detail']['available'] = 0;
            }
        }
        $afterActions = (new ListService())->processAfterPostAction($listInfo->list_id,$user_info['data']['user_id']??0,$listInfo->detail);
        $data['detail']['after_action'] = $afterActions;
        return $data;
    }

    /*
    * 文章详情
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */

    public function getElementPage_postsDetail($data,$params,$user_info,$company_id){
        $postsService = new PostsService();
        $listService = new ListService();
        $post_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        $postsService->updatePostView($post_id);
        $postsInfo = $postsService->getPosts($post_id,"post_id,list_id,user_id,title,content,source,views,kudos,create_time,update_time");
        if($postsInfo)
        {
            $postsInfo->source = json_decode($postsInfo->source,true);
            $postsInfo->source = (new UploadService())->parthSource($postsInfo->source);
            $postsInfo->source['0']['title'] = $postsInfo->title;
            $postsInfo->source['0']['post_id'] = $postsInfo->post_id;
            $postsInfo->content = htmlspecialchars_decode($postsInfo->content);
            //是否可以修改
            $postsInfo->editable = 0;
            $userinfo = \HJ\UserInfo::findFirst([
                "user_id = '".$postsInfo->user_id."'",
                "columns"=>"user_id,nick_name,true_name,user_img,company_id"
            ]);
            $posts['nick_name'] = (isset($userinfo->user_id))?$userinfo->nick_name:"";
            $posts['true_name'] = (isset($userinfo->user_id))?$userinfo->true_name:"";
            $posts['user_img'] = (isset($userinfo->user_id))?$userinfo->user_img:"";
            $posts['company_id'] = (isset($userinfo->user_id))?$userinfo->company_id:"";
            $postsInfo->user_info = $posts;
            $listInfo = $listService->getListInfo($postsInfo->list_id,"list_id,detail,list_name");
            $listInfo->detail = json_decode($listInfo->detail,true);
            if(isset($listInfo->detail['connect']) && $listInfo->detail['connect']>0)
            {
                $connectedList = $postsService->getPostsList($listInfo->detail['connect'],[],'post_id,title,source,views');
                foreach($connectedList['data'] as $pid => $pdetail)
                {
                    $connectedList['data'][$pid]->source = json_decode($pdetail->source,true);
                    $connectedList['data'][$pid]->source = (new UploadService())->parthSource($connectedList['data'][$pid]->source);
                    $new = [];
                    foreach($connectedList['data'][$pid]->source as $k2 => $v2)
                    {
                        $new[str_replace(".","_",$k2)] = $v2;
                    }
                    $connectedList['data'][$pid]->source = $new;
                }

                $postsInfo->connect_list = array_values($connectedList['data']);
                $connectedListInfo  =  $listService->getListInfo($listInfo->detail['connect'],"list_id,list_name");
                $postsInfo->connect_list_name = (($listInfo->detail['connect_name']??"")=="")?$connectedListInfo['list_name']:$listInfo->detail['connect_name'];
            }
            $postsInfo->list_name = $listInfo->list_name;
            $data['detail'] = json_decode(json_encode($postsInfo));
        }
        return $data;
    }

    /*
    * 基于用户点赞的排行
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_rankByKudos( $data,$params,$user_info,$company_id){
        //指定数据
        if(isset($data['detail']['list_id']))
        {
            $list_id = $data['detail']['list_id'];
        }
        else//页面获取
        {
            $list_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        $groups = ['user_id'];
        //查询列表内容
        $posts = \HJ\Posts::find([
            "list_id='".$list_id."' and visible=1",
            "columns"=>array_merge($groups,['count(1) as count']),
            "group"=>$groups
        ])->toArray();
        array_multisort(array_column($posts,'count'),SORT_DESC,$posts);
        foreach($posts as $p_key=>$p_val){
            $userinfo = (new UserService())->getUserInfo($p_val['user_id'],"user_id,nick_name,true_name,user_img,company_id");
            $posts[$p_key]['nick_name'] = (isset($userinfo->user_id))?$userinfo->nick_name:"";
            $posts[$p_key]['true_name'] = (isset($userinfo->user_id))?$userinfo->true_name:"";
            $posts[$p_key]['user_img'] = (isset($userinfo->user_id))?$userinfo->user_img:"";
            $posts[$p_key]['company_id'] = (isset($userinfo->user_id))?$userinfo->company_id:0;
            $posts[$p_key]['highlight'] = ($p_val['user_id'] == $user_info['data']['user_id'])?1:0;
        }
        $data['detail']['all'] = $posts;
        return $data;
    }
    /*
     * 俱乐部信息
     * userinfo 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */

    public function getElementPage_clubInfo($data,$params,$user_info,$company_id){
        //指定数据
        if(isset($data['detail']['club_id']))
        {
            $club_id = $data['detail']['club_id'];
        }
        else//页面获取
        {
            $club_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        $clubService = new ClubService();
        //获取列表
        $clubInfo = $clubService->getClubInfo($club_id,"club_id,club_name");
        $data['detail'] = $clubInfo;
        $permission = $clubService->getUserClubPermission($user_info->user_id??0,$clubInfo->club_id??0);
        $data['detail']->permission = $permission;
        return $data;
    }

     /*
     * 俱乐部成员列表
     * userinfo 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */
    public function getElementPage_clubMemberList($data,$params,$user_info,$company_id)
    {
        if (isset($data['detail']['club_id'])) {
            $club_id = $data['detail']['club_id'];
        } else//页面获取
        {
            $club_id = $this->getFromParams($params, $data['detail']['from_params'], 0);
        }
        $club_info = (new ClubService())->getClubInfo($club_id,'club_name,icon');
        $data['detail']['club_id'] = $club_id;
        $data['detail']['club_member_count'] = (new ClubService())->getClubMemberCount($club_id);
        $data['detail']['club_name'] = $club_info->club_name;
        $data['detail']['icon'] = $club_info->icon;
        $club_member_list = (new ClubService())->getClubMemberList($club_id, '*', $this->getFromParams($params, 'start', 0), $this->getFromParams($params, 'page', 1), $this->getFromParams($params, 'pageSize', 3), 1)->toArray();
        foreach ($club_member_list as $key => $value)
        {
            $club_member_info = (new UserService())->getUserInfo($value['user_id'],'user_id,nick_name,true_name,user_img');
            $club_member_list[$key]['nick_name']=$club_member_info->nick_name;
            $club_member_list[$key]['true_name']=$club_member_info->true_name;
            $club_member_list[$key]['user_img']=$club_member_info->user_img;
        }
        $data['detail']['club_member_list'] = $club_member_list;
        return $data;
    }

    /*
    * 用户参加的俱乐部列表
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_attendClubList($data,$params,$user_info,$company_id){
          $club_lsit = (new  ClubService())->getUserClubList($user_info['data']['user_id'],'club_id,club_name,icon');
          $club_list = [];
          foreach ($club_lsit as $key=> $value)
          {
              $club_list[$key]['club_id'] = $value->clubInfo->club_id;
              $club_list[$key]['club_name'] = $value->clubInfo->club_name;
              $club_list[$key]['icon'] = $value->clubInfo->icon;
          }
           $data['detail']['club_list'] = $club_list;
           return $data;
    }

    /*
     * 用户参加的活动列表
     * userinfo 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */
     public function getElementPage_attendActivityList($data,$params,$user_info,$company_id){
           $activity = (new ActivityService())->getActivityList($user_info['data']['user_id'],$this->getFromParams($params,'start'),$this->getFromParams($params,'page'),$this->getFromParams($params,'pageSize'))->toArray();
           foreach ($activity as $key=>$value)
           {
               $activity_info = (new ActivityService())->getActivityInfo($value['activity_id'],'*');
               $activity_member_count = (new ActivityService())->getActivityMemberCount($value['activity_id']);
               //活动人数
               $activity_list[$key]['count'] = $activity_member_count;
               $activity_list[$key]['activity_name'] = $activity_info->activity_name;
               $activity_list[$key]['club_id'] = $activity_info->club_id;
               $activity_list[$key]['icon'] = $activity_info->icon;
               $activity_list[$key]['start_time'] = $activity_info->start_time;
               $activity_list[$key]['end_time'] = $activity_info->end_time;
               if(time()<$activity_info->start_time)
               {
                   $status = 0;
               }elseif(time()>$activity_info->end_time)
               {
                   $status = 2;
               }else
               {
                   $status = 1;
               }
               $activity_list[$key]['status'] = $status;
           }
           $data['detail']['activity_list'] = $activity_list;
           return $data;
     }


    /*
    * 活动报名
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_activityApply($data,$params,$user_info,$company_id){

        if(isset($data['detail']['auto']) && $data['detail']['auto']==1)
        {
            $map = [];
            $map['mobile'] = $user_info['data']['mobile']??"";
            $map['user_name'] = $user_info['data']['true_name']??"";
            $map['department'] = "";
            $map['activity_id'] = $data['detail']['activity_id']??0;
            unset($data);
            return false;
        }
        else
        {
            $activityLog = (new ActivityService())->getActivityLogByUser($user_info['data']['user_id'],$data['detail']['activity_id']);
            if(!$activityLog)
            {
                $data['detail']['applied'] = 0;
            }
            else
            {
                $data['detail']['applied'] = 1;
            }
        }
        return $data;
    }

    /*
    * 活动报名
    * 创建活动入口
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_activityCreate($data,$params,$user_info,$company_id){
        $userClubListWithPermission = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $userClubListWithPermission = json_decode(json_encode($userClubListWithPermission),true);
        $positionList = (new ActivityService())->getPositionListByCreater($user_info['data']['company_id'],$user_info['data']['user_id']);
        $data['detail']['user_club_list'] =array_values($userClubListWithPermission);
        $data['detail']['member_limit'] = [100,10,3];
        $data['detail']['monthly_apply_limit'] = [1,2,3];
        $data['detail']['recent_position_list'] = array_values($positionList);

        return $data;
    }

    /*
    * 俱乐部申请记录
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_clubMemberLog($data,$params,$user_info,$company_id){

        if(isset($data['detail']['club_id']))
        {
            $club_id = $data['detail']['club_id'];
        }
        else//页面获取
        {
            $club_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        //管理的俱乐部列表
        $club_list_permission = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $club_list=[];
        foreach ($club_list_permission as $key=> $value)
        {
            $club_list[$key]['club_id'] = $value->clubInfo->club_id;
            $club_list[$key]['club_name'] = $value->clubInfo->club_name;
        }
        $data['detail']['club_list'] = $club_list;
        $club_info = (new ClubService())->getClubInfo($club_id,'club_name,icon');
        $data['detail']['club_id'] = $club_id;
        $data['detail']['club_member_count'] = (new ClubService())->getClubMemberCount($club_id);
        $data['detail']['club_name'] = $club_info->club_name;
        $data['detail']['icon'] = $club_info->icon;
        $club_member_logs = (new ClubService())->getClubMemberLogInfo($club_id,'log_id,club_id,create_time,user_id,result',$this->getFromParams($params,'start',0),$this->getFromParams($params,'page',0),$this->getFromParams($params,'pageSize',0),$this->getFromParams($params,'result',0));
        $member_log_list = [];
        foreach ($club_member_logs as $key =>$value)
        {
            $user_info = (new UserService())->getUserInfo($value->user_id??0,'user_id,nick_name,true_name,user_img');
            $member_log_list[$key]['user_id'] = $user_info->user_id;
            $member_log_list[$key]['nick_name'] = $user_info->nick_name;
            $member_log_list[$key]['true_name'] = $user_info->true_name;
            $member_log_list[$key]['user_img'] = $user_info->user_img;
            $member_log_list[$key]['log_id'] = $value->log_id;
            $member_log_list[$key]['result'] = $value->result;
            $member_log_list[$key]['apply_club_name'] = $club_info->club_name;
            $member_log_list[$key]['create_time'] = date('Y/m/d h:i',strtotime($value->create_time));
        }
        
        $data['detail']['member_log_list']= $member_log_list;
        return $data;
    }


    /*
     * 获取活动信息
     */

    public function getElementPage_activityInfo($data,$params,$user_info,$company_id){
        if(isset($data['detail']['activity']))
        {
            $activity_id = $data['detail']['activity_id'];
        }
        else//页面获取
        {
            $activity_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        $activity_info = (new ActivityService())->getActivityInfo($activity_id,'*');
        $data['detail']['activity_info'] = $activity_info;
        $detail = json_decode($activity_info->detail);
        $data['detail']['activity_info']->checkin = $detail->checkin??[];
        $chinese_start_date = date('m月d日',strtotime($activity_info->start_time)).date('h:i',strtotime($activity_info->start_time));
        $chinese_end_date = date('m月d日',strtotime($activity_info->end_time)).date('h:i',strtotime($activity_info->end_time));
        $activity_info->chinese_start_time = $chinese_start_date;
        $activity_info->chinese_end_time = $chinese_end_date;
        //$data['detail']['address'] = isset($detail['checkin']['address'])?$detail['checkin']['address']:'';
        $data['detail']['userCount'] = (new ActivityService())->getActivityMemberCount($activity_id);
        $club_info = (new ClubService())->getClubInfo($activity_info->club_id,"club_id,detail");
        $detail = json_decode($club_info->detail);
        if(isset($detail->banner))
        {
            $data['detail']['club_info'] = $detail->banner;
        }else
        {
            $data['detail']['club_info'] = [];
        }
        $member_list = (new ActivityService())->getActivityMemberList($activity_id);
        $activity_member_list = [];
        foreach ($member_list as $value)
        {
            $userInfo = (new UserService())->getUserInfo($value->user_id,'user_id,nick_name,true_name,user_img');
            $activity_member_list[] = $userInfo;
        }
        $data['detail']['activity_member_list'] = $activity_member_list;





        unset($data['detail']['activity_info']->detail);
        return $data;
    }

    /*
     * 获取用户可管理活动列表
     */
    public function getElementPage_managedActivityList($data,$params,$user_info,$company_id){
        if(isset($data['detail']['club_id']))
        {
            $club_id = $data['detail']['club_id'];
        }
        else//页面获取
        {
            $club_id = $this->getFromParams($params,$data['detail']['from_params'],'');
        }
        if(strlen($club_id) == 0)
        {
            $club_id = '';
        }
        $return  = (new ActivityService())->getUserActivityListWithPermission($user_info['data']['user_id'],$club_id,$this->getFromParams($params,'start',0),$this->getFromParams($params,'page',1),$this->getFromParams($params,'pageSize',3));
        $managed_club_list = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $managed_activity_list = $return['activity_list'];
        foreach ($managed_activity_list as $key=>$value)
        {
            if($value->club_id>0)
            {
                $managed_activity_list[$key]->club_info = (new ClubService())->getClubInfo($value->club_id,'club_id,club_name,icon');
            }else
            {
                $managed_activity_list[$key]->club_info = [];
            }
            $managed_activity_list[$key] = (object)array_merge((array)$managed_activity_list[$key],(array)$managed_activity_list[$key]->club_info);
        }
        $managed_club_list = json_decode(json_encode($managed_club_list));
        $data['detail']['managed_club_list'] = array_values($managed_club_list);
        $data['detail']['residuals'] = $return['residuals'];
        $data['detail']['managed_activity_list'] = array_values($managed_activity_list);
        return $data;
    }
    /*
     * 获取用户可管理的俱乐部列表
     */
    public function getElementPage_managedClubList($data,$params,$user_info,$company_id)
    {
        $managed_club_list = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $data['detail']['managed_club_list'] = json_decode(json_encode($managed_club_list));
        return $data;
    }


    //从页面参数重获取数据
    //$params:页面参数json串
    //$param_name: 变量名  .表示层级
    public function getFromParams($params,$param_name = "user.family.sun",$default = null)
    {
        $t = explode(".",$param_name);
        foreach($t as $key)
        {
            if(isset($params[$key]))
            {
                $params = $params[$key];
            }
            else
            {
                return $default;
            }
        }
        return $params;
    }

    /*
     * 更新活动信息页面
     */

    public function getElementPage_activityUpdate($data,$params,$user_info,$company_id){
        if(isset($data['detail']['activity_id']))
        {
            $activity_id = $data['detail']['activity_id'];
        }
        else//页面获取
        {
            $activity_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        $activity_info = (new ActivityService())->getActivityInfo($activity_id,'*');
        $data['detail']['activity_info'] = $activity_info;
        $detail = json_decode($activity_info->detail);
        $data['detail']['activity_info']->checkin = $detail->checkin;
        $data['detail']['activity_info']->monthly_apply_limit = $detail->monthly_apply_limit;
        $data['detail']['activity_info']->weekly_rebuild = $detail->weekly_rebuild;
        $data['detail']['member_limit'] = [100,10,3];
        $data['detail']['monthly_apply_limit'] = [1,2,3];
        return $data;
    }

    /*
     * 俱乐部模块首页
     */
    public function getElementPage_clubPermission($data,$params,$user_info,$company_id){
        $permission = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $permission_flag = 0;
        foreach ($permission as $value)
        {
            if($value->permission>0)
            {
                $permission_flag = 1;
                break;
            }
        }
        $data['detail']['permission'] = $permission_flag;
        return  $data;
    }
    /*
     * 活动成员列表
     */
    public function getElementPage_activityMemberList($data,$params,$user_info,$company_id){
        if(isset($data['detail']['activity_id']))
        {
            $activity_id = $data['detail']['activity_id'];
        }
        else//页面获取
        {
            $activity_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        $member_list = (new ActivityService())->getActivityMemberList($activity_id);
        $activity_member_list = [];
        foreach ($member_list as $value)
        {
            $userInfo = (new UserService())->getUserInfo($value->user_id,'user_id,nick_name,true_name,user_img');
            $activity_member_list[] = $userInfo;
        }
        $data['detail']['activity_member_list'] = $activity_member_list;
        return $data;
    }
    /*
    * 用户对应企业的轮播图列表
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_clubBannerList($data,$params,$user_info,$company_id){
        $club_list = (new  ClubService())->getClubListByCompany($user_info['data']['company_id'],'club_id,club_name,detail');
        $bannerList = [];
        foreach ($club_list as $key=> $club_info)
        {
            $detail = json_decode($club_info->clubInfo->detail??"",true);
            if(isset($detail['banner']))
            {
                foreach($detail['banner'] as $k => $banner)
                {
                    if(count($bannerList)<=20)
                    {
                        $bannerList[] = $banner;
                    }
                    else
                    {
                        break;
                    }
                }
            }
        }
        $data['detail']['banner_list'] = $bannerList;
        return $data;
    }
    /*
    * 报名中的活动
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_applyingAcitivity($data,$params,$user_info,$company_id){
        $activity_list = (new ActivityService())->getActivityListByCompany($user_info['data']['company_id'],'activity_id,club_id,activity_name,apply_start_time,apply_end_time,start_time,end_time',$club_id ='',0);
        $currentTime = time();
        $clubService = new ClubService();
        foreach ($activity_list as $key=> $activity_info)
        {
            if((strtotime($activity_info->apply_start_time)<=$currentTime) && (strtotime($activity_info->apply_end_time)>=$currentTime))
            {
                $clubInfo = $clubService->getClubInfo($activity_info->club_id,"club_id,club_name,icon");
                $activity_list[$key] = (object)array_merge((array)$activity_info,(array)$clubInfo);
                $chinese_start_date = date('m月d日',strtotime($activity_info->start_time))." 周".$this->weekarray[date('w',strtotime($activity_info->start_time))];
                $chinese_end_date = date('m月d日',strtotime($activity_info->end_time))." 周".$this->weekarray[date('w',strtotime($activity_info->end_time))];
                $activity_list[$key]->chinese_start_date = $chinese_start_date;
                $activity_list[$key]->chinese_end_date = $chinese_end_date;
            }
            else
            {
                unset($activity_list[$key]);
            }
        }
        $data['detail']['activity_list'] = array_values($activity_list);
        return $data;
    }

}