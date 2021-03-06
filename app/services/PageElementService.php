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
        $listInfo = (new ListService())->getListInfo($list_id,"list_id,list_type,detail,list_name");
        $listInfo_detali = json_decode($listInfo->detail,true);
        if(isset($listInfo_detali['header_url']))
        {
            $data['detail']['header_url'] = $listInfo_detali['header_url'];
        }
        $data['detail']['list_id'] = $listInfo->list_id;
        $data['detail']['list_name'] = $listInfo->list_name;
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
        $data['data'] = (new PostsService())->getPostsList($listInfo->list_id,$userList,"*","post_id DESC",$this->getFromParams($params,"start",0),$this->getFromParams($params,"page",1),$this->getFromParams($params,"page_size",3),0);
        foreach($data['data']['data'] as $k => $postDetail)
        {
            $data['data']['data'][$k]->source = json_decode($postDetail->source,true);
            $data['data']['data'][$k]->source = (new UploadService())->parthSource($data['data']['data'][$k]->source);
            $data['data']['data'][$k]->source[0]['post_id'] = $postDetail->post_id;
            $data['data']['data'][$k]->source[0]['title'] = $postDetail->title;
            $data['data']['data'][$k]->list_type = $listInfo->list_type;
            $data['data']['data'][$k]->content = htmlspecialchars_decode($postDetail->content);
            $data['data']['data'][$k]->key = $k;
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
                $data['detail']['jump_urls'] = array_values($data['detail']['jump_urls']);
                $data['name'] = $navkey;
                $nav_type = explode('#',str_replace('"', '', $nacvalue));
                $data['url'] = reset($nav_type);
                $data['type'] = explode('.',str_replace('"', '', end($nav_type)));
                $navList[] = $data;
            }
            $data['detail']['jump_urls'] = json_decode(json_encode($navList),true);
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
        $company_info = (new CompanyService())->getCompanyInfo($company_id);
        $company_info = json_decode(json_encode($company_info),true);
        if(isset($company_info['detail']))
        {
           $company_info['detail'] = json_decode($company_info['detail'],true);
        }
        $data['detail'] = $company_info;
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
        $listService = new ListService();
        if(isset($data['detail']['list_id']))
        {
            $list_id = $data['detail']['list_id'];
        }
        else
        {
            $list_id = $this->getFromParams($params,$data['detail']['from_params'],0);
        }
        //获取列表信息
        $listInfo = $listService->getListInfo($list_id,"list_id,activity_id,detail");
        $listInfo->detail = json_decode($listInfo->detail,true);
        $data['detail']['available'] = $listService->checkUserListPermission($user_info['data']['user_id'],$list_id)['result']??1;
        $afterActions = $listService->processAfterPostAction($listInfo->list_id,$user_info['data']['user_id']??0,$listInfo->detail);
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
        $postsInfo = $postsService->getPosts($post_id,"post_id,list_id,user_id,title,content,source,views,kudos,create_time,update_time",0);
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
                    if($post_id == $pdetail->post_id)
                    {
                        unset($connectedList['data'][$pid]);
                        continue;
                    }
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
            //查询用户是否已经点赞
            $postskudos_info = (new PostsService())->checkKudos($user_info['data']['user_id']??0,"",$post_id);
            if($postskudos_info)
            {
                $postsInfo->is_kudos = 1;
            }else{
                $postsInfo->is_kudos = 0;
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
  //      $groups = ['user_id'];
        //查询列表内容
//        $posts = \HJ\Posts::find([
//            "list_id='".$list_id."' and visible=1",
//            "columns"=>'count(1) as count',
//            "group"=>$groups
//        ])->toArray();
//        print_r($posts);die();
        $page = $this->getFromParams($params,'page',1);
        $pageSize = $this->getFromParams($params,'pageSize',150);
        //新表查询
        $posts = \HJ\ActivityListRank::find([
          'list_id ='.$list_id,
            'columns'=>'log_id,user_id,kudos_count+plus as count',
            'order'=>'count desc',
             "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
            ]
        )->toArray();
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
          $user_id = $user_info['data']['user_id'];
          $club_lsit = (new  ClubService())->getUserClubList($user_id,'club_id,club_name,icon');
          $club_list = [];
          foreach ($club_lsit as $key=> $value)
          {
              $club_list[$key]['user_count'] = (new ClubService())->getClubMemberCount($value->clubInfo->club_id);
              $club_list[$key]['activity_count'] = (new ClubService())->getClubActivityCount($value->clubInfo->club_id);
              $club_list[$key]['club_id'] = $value->clubInfo->club_id;
              $club_list[$key]['club_name'] = $value->clubInfo->club_name;
              $club_list[$key]['icon'] = $value->clubInfo->icon;
              $club_list[$key]['result'] = 1;
          }
          //下面为用户待审核的俱乐部列表
          $club_log_list = [];
          $user_club_log = (new ClubService())->getUserElationClub($user_id);
          foreach ($user_club_log as $key=> $value)
          {
              $club_log_list[$key]['user_count'] = (new ClubService())->getClubMemberCount($value->club_id);
              $club_log_list[$key]['activity_count'] = (new ClubService())->getClubActivityCount($value->club_id);
              $club_info = (new ClubService())->getClubInfo($value->club_id,'club_id,club_name,icon');
              $club_log_list[$key]['club_id'] = $club_info->club_id;
              $club_log_list[$key]['club_name'] = $club_info->club_name;
              $club_log_list[$key]['icon'] = $club_info->icon;
              $club_log_list[$key]['result'] = 0;
          }
          $club_list = array_merge($club_list,$club_log_list);
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
           $app_id = $this->getFromParams($params,'app_id',101);
           $activity = (new ActivityService())->getActivityList($user_info['data']['user_id'])->toArray();
           $activity_list = [];
           foreach ($activity as $key=>$value)
           {
               $activity_info = (new ActivityService())->getActivityInfo($value['activity_id'],'*');
               if(!isset($activity_info->status) || $activity_info->status == 0 || $activity_info->system == 1 || $activity_info->app_id != $app_id)
               {
                   continue;
               }
               $activity_member_count = (new ActivityService())->getActivityMemberCount($value['activity_id']);
               //活动人数
               $activity_list[$key]['Usercount'] = $activity_member_count;
               $activity_list[$key]['activity_id'] = $value['activity_id'];
               $activity_list[$key]['activity_name'] = $activity_info->activity_name;
               $activity_list[$key]['create_time'] = $value['create_time']; //报名记录创建的时间最近的排最前
               $activity_list[$key]['start_time'] = date('Y-m-d H:i',strtotime($activity_info->start_time));
               $activity_list[$key]['end_time'] = date('Y-m-d H:i',strtotime($activity_info->end_time));
               $activity_list[$key]['apply_start_time'] = date('Y-m-d H:i',strtotime($activity_info->apply_start_time));
               $activity_list[$key]['apply_end_time'] = date('Y-m-d H:i',strtotime($activity_info->apply_end_time));

               //俱乐部信息
               $club_info = (new ClubService())->getClubInfo($activity_info->club_id);
               $activity_list[$key]['club_id'] = $activity_info->club_id??0;
               $activity_list[$key]['club_name'] = $club_info->club_name??'未关联俱乐部';
               $activity_list[$key]['icon'] = $club_info->icon??'';

               $detail = json_decode($activity_info->detail,true);
               if(isset($detail['checkin'])&&$detail['checkin'])
               {
                   $activity_list[$key]['position'] = $detail['checkin']['address']??'';
               }else
               {
                   $activity_list[$key]['position'] = [];
               }

               if(time()<strtotime($activity_info->start_time))
               {
                   $status = 0; //未开始的活动
               }elseif(time()>strtotime($activity_info->end_time))
               {
                   $status = 2;//已结束
               }else
               {
                   $status = 1; //正在进行中
               }
               $activity_list[$key]['status'] = $status;
           }
           //对状态进行排序 已结束的排最后 最近报名的排前面
           $create_time_sort = array_column($activity_list,'create_time');
           $status_sort = array_column($activity_list,'status');
           array_multisort($status_sort,SORT_ASC,$create_time_sort,SORT_DESC,$activity_list);
           $page = $this->getFromParams($params,'page',1);
           $pageSize = $this->getFromParams($params,'pageSize',4);
           $offset = ($page-1)*$pageSize;
             if($offset+$pageSize >= count($activity_list))
             {
                 $residuals = 0;
             }else
             {
                 $residuals = 1;
             }
             $activity_list = array_slice($activity_list,$offset,$pageSize);

           $data['detail']['activity_list'] = $activity_list;
           $data['detail']['residuals'] = $residuals;
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
            //判断用户是否已参加活动
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
        $data['detail']['member_limit'] = ['100','10','3','不限'];
        $data['detail']['monthly_apply_limit'] = ['1次','2次','3次','不限'];
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
    public function getElementPage_clubMemberLog($data,$params,$user_info,$company_id)
    {

        if (isset($data['detail']['club_id'])) {
            $club_id = $data['detail']['club_id'];
        } else//页面获取
        {
            $club_id = $this->getFromParams($params, $data['detail']['from_params'], -1);
        }
        //管理的俱乐部列表
        $club_list_permission = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $club_list = [];
        $club_ids = [];
        $sum = 0;
        foreach ($club_list_permission as $key => $value) {
            $Usercount = (new ClubService())->getClubMemberCount($value->club_id);
            $sum += $Usercount;
            $club_list[$key]['Usercount'] = $Usercount;
            $club_list[$key]['club_id'] = $value->club_id;
            $club_list[$key]['club_name'] = $value->club_name;
            $club_ids [] = $value->club_id;
        }
        $club_list = array_values($club_list);
        $all = [
            'club_id'=>-1,
            'club_name'=>'全部',
            'Usercount'=>$sum
        ];
        array_push($club_list,$all);
        $data['detail']['club_list'] = $club_list;
        //默认第一个俱乐部
        $result = $this->getFromParams($params, 'result', 0);
        $member_log_list = [];
        if ($result == 3 || $club_id == -1) {
            $club_id = $club_ids;
        }
            $club_member_logs = (new ClubService())->getClubMemberLogInfo($club_id, 'log_id,club_id,create_time,user_id,result', $this->getFromParams($params, 'start', 0), $this->getFromParams($params, 'page', 1), $this->getFromParams($params, 'pageSize', 3), $this->getFromParams($params, 'result', 0));
            foreach ($club_member_logs as $key => $value) {
                $user_info = (new UserService())->getUserInfo($value->user_id ?? 0, 'user_id,nick_name,true_name,user_img');

                $user_count = (new ClubService())->getClubMemberCount($value->club_id);
                $member_log_list[$key]['user_id'] = $user_info->user_id??0;
                $member_log_list[$key]['user_count'] = $user_count;
                $member_log_list[$key]['nick_name'] = $user_info->nick_name??'';
                $member_log_list[$key]['true_name'] = $user_info->true_name??'';
                $member_log_list[$key]['user_img'] = $user_info->user_img??'';
                $member_log_list[$key]['log_id'] = $value->log_id;
                $member_log_list[$key]['result'] = $value->result;
                $member_log_list[$key]['club_id'] = $value->club_id;
                $club_info = (new ClubService())->getClubInfo($value->club_id,'club_id,club_name');
                $member_log_list[$key]['club_name'] = $club_info->club_name;
                $member_log_list[$key]['create_time'] = date('Y-m-d H:i', strtotime($value->create_time));
            }
        $data['detail']['member_log_list'] = array_values($member_log_list);
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
        //活动剩余名额
        $detail = json_decode($activity_info->detail);
        //初始化位置信息
        $checkin = new \stdClass;
        $checkin->longitude = 0;
        $checkin->latitude = 0;
        $checkin->address = '';
        $activity_checkin = empty($detail->checkin)?$checkin:$detail->checkin;
         $data['detail']['activity_info']->checkin = $activity_checkin;

        //各种不同的日期格式.....
        $chinese_apply_start_date = date('m月d日 H:i',strtotime($activity_info->apply_start_time));
        $chinese_apply_end_date = date('m月d日 H:i',strtotime($activity_info->apply_end_time));
        $activity_info->chinese_apply_start_time = $chinese_apply_start_date;
        $activity_info->chinese_apply_end_time = $chinese_apply_end_date;
        $chinese_start_date = date('m月d日 H:i',strtotime($activity_info->start_time));
        $chinese_end_date = date('m月d日 H:i',strtotime($activity_info->end_time));
        $activity_info->chinese_start_time = $chinese_start_date;
        $activity_info->chinese_end_time = $chinese_end_date;
        $activity_info->format_apply_start_time = date('Y/m/d',strtotime($activity_info->apply_start_time));
        $activity_info->format_apply_end_time = date('Y/m/d',strtotime($activity_info->apply_end_time));
        $activity_info->format_start_time = date('Y/m/d',strtotime($activity_info->start_time));
        $activity_info->format_end_time = date('Y/m/d',strtotime($activity_info->end_time));
        //活动时间拼接
        if(date('Y',strtotime($activity_info->start_time)) != date('Y',strtotime($activity_info->end_time)))
        {  //跨年加上年份
            $activity_info->miniprogram_start_time_format = date('Y年m月d日 H:i',strtotime($activity_info->start_time)).'-'.date('Y年m月d日 H:i',strtotime($activity_info->end_time));
        }elseif(date('m-d',strtotime($activity_info->start_time)) != date('m-d',strtotime($activity_info->end_time))){
            //跨天的加上月份
            $activity_info->miniprogram_start_time_format = date('m月d日 H:i',strtotime($activity_info->start_time)).'-'.date('m月d日 H:i',strtotime($activity_info->end_time));
        }else
        {
            $activity_info->miniprogram_start_time_format = date('m月d日 H:i',strtotime($activity_info->start_time)).'-'.date('H:i',strtotime($activity_info->end_time));
        }

        //活动头图
        $header_image = $detail->header_image??'';
        $activity_info->header_image = $header_image;
        //如果头图不存在 取默认图片
        //公司名
        $company_info = (new  CompanyService())->getCompanyInfo($company_id,'company_id,company_name,detail');
        $activity_info->company_name = $company_info->company_name;
       
        if(!$header_image)
        {
            $detail = json_decode($company_info->detail,true);
                    $bannerList = [];
                    //需默认Banner
                    if(isset($detail['clubBanner']))
                    {
                        $bannerList = $detail['clubBanner'];
                    }
                    $default_header_image = $bannerList[0]['img_url'];
        }else
        {
            $default_header_image = '';
        }
        $activity_info->defalut_header_image = $default_header_image;



        //$data['detail']['address'] = isset($detail['checkin']['address'])?$detail['checkin']['address']:'';
        $user_count = (new ActivityService())->getActivityMemberCount($activity_id);
        $data['detail']['userCount'] = $user_count;
        if($activity_info->member_limit)
        {
            $data['detail']['activity_info']->remain = $activity_info->member_limit - $user_count;
        }else
        {
            $data['detail']['activity_info']->remain = '不限';
        }

        $data['detail']['activity_info']->activity_name = mb_substr($activity_info->activity_name,0,20);
        // 用户是否已报名
        $res = (new ActivityService())->checkUserActivity($user_info['data']['user_id'],$activity_id);
        $data['detail']['aplied'] = isset($res->id)?1:0;
        //活动关联的俱乐部信息
        $club_info = (new ClubService())->getClubInfo($activity_info->club_id,"club_id,icon,detail");
        $club_info = json_decode(json_encode($club_info),true);
        if($club_info)
        {
            $data['detail']['icon'] = $club_info['icon'];
        }else
        {
            $data['detail']['icon'] = '';
        }
        $data['detail']['club_info'] = $club_info;
        $detail = json_decode($club_info['detail']??'');
        unset($data['detail']['club_info']['detail']);
        //需默认banner
        if($detail && isset($detail->banner))
        {
            //存在且有banner
            $data['detail']['club_info']['banner']= $detail->banner;
        }else
        {
            $data['detail']['club_info']['banner'] = [];
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
            $club_id = $this->getFromParams($params,$data['detail']['from_params'],-1);
        }
        $app_id = $this->getFromParams($params,'app_id',101);

        $return  = (new ActivityService())->getUserActivityListWithPermission($app_id,$user_info['data']['user_id'],$club_id,
            'activity_id,activity_name,start_time,apply_start_time,apply_end_time,end_time,club_id,status,detail,create_time,system,app_id',$this->getFromParams($params,'start',0),$this->getFromParams($params,'page',1),$this->getFromParams($params,'pageSize',5),$this->getFromParams($params,'activity_status',-1));
        $managed_club_list = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);
        $managed_activity_list = $return['activity_list'];
        foreach ($managed_activity_list as $key=>$value)
        {
            $detail = json_decode($value['detail']);
            $managed_activity_list[$key]['address'] = $detail->checkin->address??'';
            $managed_activity_list[$key]['applied'] = (new ActivityService())->getActivityMemberCount($value['activity_id']);//参加人数
            if(date('Y',strtotime($value['start_time'])) != date('Y',strtotime($value['end_time'])))
            {
                $managed_activity_list[$key]['chinese_start_time'] = date('Y年m月d日 H:i',strtotime($value['start_time'])).'-'.date('Y年m月d日 H:i',strtotime($value['end_time']));
            }elseif(date('m-d',strtotime($value['start_time'])) != date('m-d',strtotime($value['end_time']))){
                $managed_activity_list[$key]['chinese_start_time'] = date('m月d日 H:i',strtotime($value['start_time'])).'-'.date('m月d日 H:i',strtotime($value['end_time']));
            }else
            {
                $managed_activity_list[$key]['chinese_start_time'] = date('m月d日 H:i',strtotime($value['start_time'])).'-'.date('H:i',strtotime($value['end_time']));
            }
            if($value['club_id']>0)
            {
                $club_info = (new ClubService())->getClubInfo($value['club_id'],'club_id,club_name,icon');
                $club_info = json_decode(json_encode($club_info),true);
                $club_info['club_id'] = $club_info['club_id']??0;
                $club_info['club_name'] = $club_info['club_name']??'未关联俱乐部';
                $club_info['icon'] = $club_info['icon']??'';
                $managed_activity_list[$key]['club_info'] = $club_info;
            }else
            {
                $managed_activity_list[$key]['club_info'] = [
                    'club_id'=>0,
                    'club_name'=>'未关联俱乐部',
                    'icon'=>''
                ];
            }
            $managed_activity_list[$key] = array_merge($managed_activity_list[$key],$managed_activity_list[$key]['club_info']);
        }
        $managed_club_list = json_decode(json_encode($managed_club_list),true);
        $all = [
            'club_id'=>-1,
            'club_name'=>'全部'
        ];

        array_push($managed_club_list,$all);
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
        $data['detail']['activity_info']->format_apply_start_time = date('m/d H:i',strtotime($activity_info->apply_start_time)).'-'.date('m/d H:i',strtotime($activity_info->apply_end_time));
        $data['detail']['activity_info']->format_start_time = date('Y/m/d H:i',strtotime($activity_info->start_time)).'-'.date('H:i',strtotime($activity_info->end_time));
        $data['detail']['member_limit'] = ['100','10','3','不限'];
        $data['detail']['monthly_apply_limit'] = ['1次','2次','3次','不限'];
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
        $company_info = (new  CompanyService())->getCompanyInfo($user_info['data']['company_id'],'company_id,detail');
        $detail = json_decode($company_info->detail,true);
        $bannerList = [];
        //需默认Banner
        if(isset($detail['clubBanner']))
        {
            $bannerList = $detail['clubBanner'];
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
        $culture = $this->getFromParams($params,'culture',0);
        $app_id = $this->getFromParams($params,'app_id',101);
        $already_applied = $this->getFromParams($params,'already_applied',0); //已参加的活动 0未参加
        $activity_list = (new ActivityService())->getActivityListByCompany($user_info['data']['company_id'],'activity_id,status,club_id,activity_name,comment,icon,system,apply_start_time,app_id,apply_end_time,start_time,end_time',$club_id = -1);
        $currentTime = time();
        $clubService = new ClubService();
        foreach ($activity_list as $key=> $activity_info)
        {
                if($culture)
                {
                    //文体汇活动
                    if($activity_info->system == 0)
                    {
                        unset($activity_list[$key]);
                        continue;
                    }
                }else
                {
                    //去除文体汇的活动
                    if (!$activity_info || $activity_info->system == 1 || $activity_info->app_id != $app_id) {
                        unset($activity_list[$key]);
                        continue;
                    }
                    //用户已报名的进行中的活动列表
                    // 去掉已参加的活动
                    if (!$already_applied) {
                        $user_activity_log = (new ActivityService())->getActivityLogByUser($user_info['data']['user_id'], $activity_info->activity_id);
                        if (isset($user_activity_log->id)) {
                            unset($activity_list[$key]);
                            continue;
                        }
                    }
                }
                if (($activity_info->status == 1) && (strtotime($activity_info->apply_start_time) <= $currentTime) && (strtotime($activity_info->apply_end_time) >= $currentTime)) {
                    //小程序club_id为0
                    if($activity_info->club_id == 0)
                    {
                        $clubInfo = [];
                    }else
                    {
                        $clubInfo = $clubService->getClubInfo($activity_info->club_id, "club_id,club_name,icon,detail");
                        $activity_list[$key]->club_name = $clubInfo->club_name;
                    }
                    $activity_list[$key] = (object)array_merge((array)$activity_info, (array)$clubInfo);
                    $chinese_start_date = date('m月d日', strtotime($activity_info->start_time)) . " 周" . $this->weekarray[date('w', strtotime($activity_info->start_time))];
                    $chinese_end_date = date('m月d日', strtotime($activity_info->end_time)) . " 周" . $this->weekarray[date('w', strtotime($activity_info->end_time))];
                    $activity_list[$key]->chinese_start_date = $chinese_start_date;
                    $activity_list[$key]->chinese_end_date = $chinese_end_date;
                    $activity_list[$key]->chinese_end_date = $chinese_end_date;
                    $activity_list[$key]->format_apply_time = date('m-d H:i',strtotime($activity_info->apply_start_time)).'-'.date('m-d H:i',strtotime($activity_info->apply_end_time));
                    $activity_list[$key]->activity_name = mb_substr($activity_info->activity_name, 0, 12, 'utf-8');
                    $activity_list[$key]->comment = mb_substr($activity_info->comment, 0, 12, 'utf-8');
                } else {
                    unset($activity_list[$key]);
                }

        }
        $activity_list = array_values($activity_list);
        $page = $this->getFromParams($params,'page',1);
        $pageSize = $this->getFromParams($params,'pageSize',5);
        $offset = ($page-1)*$pageSize;
        if($offset+$pageSize >= count($activity_list))
        {
            $residuals = 0;
        }else
        {
            $residuals = 1;
        }
        $activity_list = array_slice($activity_list,$offset,$pageSize);
        $data['detail']['activity_list'] = $activity_list;
        $data['detail']['residuals'] = $residuals;
        return $data;
    }

    /*
     * 精品课列表
     */

    public function getElementPage_boutique($data,$params,$user_info,$company_id)
    {
        $company_info = (new  CompanyService())->getCompanyInfo($user_info['data']['company_id']);
        $detail = json_decode($company_info->detail);
        $boutique = isset($detail->boutique)?$detail->boutique:[];
        foreach ($boutique as $key => $value) {
            $list_info = (new ListService())->getListInfo($value, 'list_id,list_name');
            $list_artical = new stdClass();
            $post_list = (new PostsService())->getPostsList($value, [], 'post_id,views,title,source');
            if (!empty($post_list['data'])) {
                $list_artical = $post_list['data'][0];
                $list_artical->title = mb_substr($post_list['data'][0]->title, 0, 12, 'utf-8');
                $source = json_decode($list_artical->source, true);
                $result = (new UploadService())->parthSource($source);
                $result = (new UploadService())->sortSource($result);
                foreach ($result as $key => $source_detail) {
                    if (!isset($header_img)) {
                        if ($source_detail['type'] == "pic") {
                            $header_img = $source_detail['path'];
                        }
                    }
                    if (!isset($header_video_img)) {
                        if ($source_detail['type'] == "video") {
                            $header_video_img = $source_detail['path'] . $source_detail['suffix'];
                        }
                    }
                }
                $list_artical->header_img = isset($header_img) ? $header_img : '';
                $list_artical->header_video_img = isset($header_video_img) ? $header_video_img : '';
                unset($header_img);
                unset($header_video_img);
                $list_artical->source = array_values($result);
                $list_artical->list_name = mb_substr($list_info->list_name, 0, 2, 'utf-8');
                $data['detail'][] = $list_artical;
            }
            else
                {
                    continue;
                }

            }
            return $data;

        }


    /*
     * 活动签到列表页
     */

    public function getElementPage_attendActivityListToCheckin($data,$params,$user_info,$company_id){
        $app_id = $this->getFromParams($params,'app_id',101);
        $checkin_time = $this->config->activity->activity_checkin_time;
        $activityList = (new ActivityService())->getActivityList($user_info['data']['user_id']);
        $activity_list = [];
        //企业信息
        $company_info = (new  CompanyService())->getCompanyInfo($company_id,'company_id,company_name,detail');

        foreach ($activityList as $key=>$value)
        {

            $activity_info = (new ActivityService())->getActivityInfo($value->activity_id,'*');

            if( $activity_info->status== 0)
            {
                continue;
            }
            if(!$activity_info)
            {
                continue;
            }
            if($activity_info->system == 1 || $activity_info->app_id != $app_id)
            {
                continue;
            }
            //可以签到的时间
            $checkin_doing_time =strtotime($activity_info->start_time)-$checkin_time;
            $activity_status = 0;
            $activity_status_name = '去签到';
            $activity_color = '#cccccc';
            $activity_checkin_time ='';

            if(time()<$checkin_doing_time)
            { //活动开始前一小时的活动剔除
                continue;
            }elseif(time()>=strtotime($activity_info->end_time) && $value->checkin_status == 0)
            {//已结束的活动且未签到
                $activity_status = 2;
                $activity_status_name = '已过期';
                $activity_color = '#DDDDDD';
            }elseif($value->checkin_status !=0)
            {
                $activity_status = 1; //已签到
                $activity_status_name = '签到成功';
                $activity_color = '#444054';
                //已签到过获取签到时间
                $detail = json_decode($value->detail);
                $activity_checkin_time = date('H:i',strtotime($detail->checkin_time??'00:00'));
            }elseif($value->checkin_status == 0)
            {
                $activity_status = 0; //去签到
                $activity_status_name = '去签到';
                $activity_color = '#3678E1';
            }
            $activity_list[$key]['activity_status'] = $activity_status;
            $activity_list[$key]['activity_status_name'] = $activity_status_name;
            $activity_list[$key]['activity_color'] = $activity_color;
            $activity_list[$key]['activity_checkin_time'] = $activity_checkin_time;

            $club_info = (new ClubService())->getClubInfo($activity_info->club_id,'club_id,club_name,icon');
            $detail = json_decode($activity_info->detail);
            $address = $detail->checkin->address??'';
            $activity_member_count = (new ActivityService())->getActivityMemberCount($value->activity_id);
            //活动已签到人数
            $checkin_count = (new ActivityService())->getActivityCheckinCount($activity_info->activity_id);
            $activity_list[$key]['activity_id'] = $activity_info->activity_id;
            $activity_list[$key]['checkin_count'] = $checkin_count;
            $activity_list[$key]['activity_name'] = $activity_info->activity_name;
            $activity_list[$key]['club_id'] = $activity_info->club_id??0;
            $activity_list[$key]['club_icon'] = $club_info->icon??'';
            $activity_list[$key]['club_name'] = $club_info->club_name??'';
            $activity_list[$key]['start_time'] = $activity_info->start_time;
            $activity_list[$key]['end_time'] = $activity_info->end_time;

            //活动头图
            $header_image = $detail->header_image??'';
            $activity_list[$key]['header_image'] = $header_image;
            //如果头图不存在 取默认图片
            $activity_list[$key]['company_name'] = $company_info->company_name;

            if(!$header_image)
            {
                $detail = json_decode($company_info->detail,true);
                $bannerList = [];
                //需默认Banner
                if(isset($detail['clubBanner']))
                {
                    $bannerList = $detail['clubBanner'];
                }
                $default_header_image = $bannerList[0]['img_url'];
            }else
            {
                $default_header_image = '';
            }
            $activity_list[$key]['defalut_header_image'] = $default_header_image;


            $hour = date('H', strtotime($activity_info->start_time));
            if($hour>12)
            {
                $now = '下午';
            }else
            {
                $now = '上午';
            }
            $time = date('H:i', strtotime($activity_info->start_time));
            $activity_list[$key]['now'] = $now;
            $activity_list[$key]['time'] = $time;
            $activity_list[$key]['chinese_start_time'] = date('m月d日',strtotime($activity_info->start_time));
            $activity_list[$key]['chinese_end_time'] = date('m月d日',strtotime($activity_info->end_time));
            $activity_list[$key]['checkin_status'] = $value->checkin_status;
            $activity_list[$key]['address'] = $address;

        }
        $checkin_status = array_column($activity_list,'activity_status');
        $start_time = array_column($activity_list,'start_time');
        array_multisort($checkin_status,SORT_ASC,$start_time,SORT_DESC,$activity_list);
//        $page = $this->getFromParams($params,'page',1);
//        $pageSize = $this->getFromParams($params,'pageSize',4);
//        $offset = ($page-1)*$pageSize;
//        if($offset+$pageSize >= count($activity_list))
//        {
//            $residuals = 0;
//        }else
//        {
//            $residuals = 1;
//        }
//        $activity_list = array_slice($activity_list,$offset,$pageSize);
        $data['detail']['activity_list'] = $activity_list;
//        $data['detail']['residuals'] = $residuals;
        return $data;
    }
    /*
    * 步数统计信息
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_stepsData($data,$params,$user_info,$company_id){
        $userService = new UserService();
        $stepsConfig = $this->config->steps;
        //日期范围类型  day日期 week周 month月 3month3个月 halfyear半年 year年
        $dateRangeType = $this->getFromParams($params,'date_range_type',"month");
        //日期端类型 1自然 2当前推
        $dateType = $this->getFromParams($params,'date_type',1);
        //$dateRange = (new Common())->processDateRange($dateRangeType,$dateType);
        $currentTime = time();
        $currentDate = date("Y-m-d",$currentTime);
        $dateRange = (new StepsService())->getStepsDateRange($user_info['data']['company_id'],$currentDate);
        $dateRange = $dateRange['data'][$dateRangeType];
        $departmentId = $this->getFromParams($params,'department_id',"");
        if($departmentId>0)
        {
            $userInfo = $userService->getUserInfo($user_info['data']['user_id'],"user_id,company_id,department_id");
            $department = (new DepartmentService())->getDepartment($userInfo->department_id);
            if($department['current_level']==3)
            {
                $departmentId = $department['department_id_2'];
            }
            else
            {
                $name = "department_id_".$department['current_level'];
                $departmentId = $department[$name];
            }
        }
        $stepsData = (new StepsService())->getStepsDataByDate($user_info['data']['user_id'],$dateRange,$user_info['data']['company_id'],$departmentId,"user_id",$this->getFromParams($params, 'page', 1), $this->getFromParams($params, 'pageSize', 20));
        $stepsList = $stepsData['list'];
        $companyInfo = (new CompanyService())->getCompanyInfo($user_info['data']['company_id'],"company_id,detail");
        $companyInfo->detail = json_decode($companyInfo->detail,true);
        $stepsGoal = $companyInfo->detail['daily_step']??$stepsConfig->defaultDailyStep * $dateRange['days'];
        $page = $this->getFromParams($params, 'page', 1);
        $pageSize = $this->getFromParams($params, 'pageSize', 30);
        $startRank = ($page-1)*$pageSize+1;
        foreach($stepsList as $key => $detail)
        {
            $stepsList[$key]['Rank'] = $startRank++;
            $stepsList[$key]['distance'] = intval($detail['totalStep']*$stepsConfig->distancePerStep);
            $stepsList[$key]['kcal'] = intval($detail['totalStep']/$stepsConfig->stepsPerKcal);
            $stepsList[$key]['time'] = intval($detail['totalStep']/$stepsConfig->stepsPerMinute);
            $userInfo = $userService->getUserInfo($detail['user_id'],"user_id,nick_name,true_name,user_img,department_id",1);
            if(!isset($userInfo->user_id))
            {
                $userInfo = ["user_img"=>"","true_name"=>"未知用户","user_id"=>$userInfo];
            }
            $stepsList[$key]['userInfo'] = $userInfo;
            $stepsList[$key]['goal'] = $stepsGoal;
            $stepsList[$key]['achive'] = ($detail['totalStep']>=$stepsGoal)?1:0;
            $stepsList[$key]['achive_rate'] = intval(100*($detail['totalStep']/$stepsGoal));
        }
        $level= array_column($stepsList,'totalStep');
        array_multisort($level,SORT_DESC,$stepsList);
        $data['detail']['steps'] = $stepsList;
        $data['detail']['mine'] = $stepsData['mine'];
        $data['detail']['mine']['user_info'] = (new UserService())->getUserInfo($user_info['data']['user_id'],'user_id,nick_name,true_name,user_img');
        return $data;
    }
    /*
    * 用户步数统计信息
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_userStepsData($data,$params,$user_info,$company_id){
        $userService = new UserService();
        //日期范围类型  day日期 week周 month月 3month3个月 halfyear半年 year年
        $dateRangeType = $this->getFromParams($params,'date_range_type',"day");
        //日期端类型 1自然 2当前推
        $dateType = $this->getFromParams($params,'date_type',1);
        //$dateRange = (new Common())->processDateRange($dateRangeType,$dateType);
        $userId = $this->getFromParams($params,'user_id',0);
        $userInfo = $userService->getUserInfo($userId,"user_id,company_id,department_id");
        if(isset($userInfo->user_id) && $userInfo->company_id == $user_info['data']['company_id'])
        {
            //用户指定的用户找到，且是属于同一家公司
            //$userInfo = $userService->getUserInfo($user_info,"user_id,company_id,department_id");
        }
        else
        {
            //指定为当前用户
            $userId = $user_info['data']['user_id'];
            $userInfo = $userService->getUserInfo($userId,"user_id,company_id,department_id");
        }
        $currentTime = time();
        $currentDate = date("Y-m-d",$currentTime);
        $currentDateRange = (new StepsService())->getStepsDateRange($user_info['data']['company_id'],$currentDate);
        $dateRange = $currentDateRange['data'][$dateRangeType];
        $stepsData = (new StepsService())->getUserStepsDataByDate($dateRange,$user_info['data']['company_id'],$userInfo->user_id);
        $t  = [];
        for($date = (!isset($dateRange['date'])?$dateRange['endDate']:$dateRange['date']);$date>=(!isset($dateRange['date'])?$dateRange['startDate']:$dateRange['date']);$date = date("Y-m-d",strtotime($date)-86400))
        {
            $t[$date] = ["date"=>$date,"totalStep"=>0];
        }
        $companyInfo = (new CompanyService())->getCompanyInfo($user_info['data']['company_id'],"company_id,detail");
        $companyInfo->detail = json_decode($companyInfo->detail,true);
        $stepsConfig = $this->config->steps;
        $stepsGoal = $companyInfo->detail['daily_step']??$stepsConfig->defaultDailyStep;
        foreach($stepsData as $key => $detail)
        {
            $t[$detail['date']] = array_merge($t[$detail['date']],$detail);
        }
        foreach($t as $date => $detail)
        {
            $t[$date]['distance'] = intval($detail['totalStep']*$stepsConfig->distancePerStep);
            $t[$date]['kcal'] = intval($detail['totalStep']/$stepsConfig->stepsPerKcal);
            $t[$date]['time'] = intval($detail['totalStep']/$stepsConfig->stepsPerMinute);
            $t[$date]['goal'] = $stepsGoal;
            $t[$date]['achives'] = ($detail['totalStep']>=$stepsGoal)?1:0;
            $t[$date]['achive_rate'] = intval(100*($detail['totalStep']/$stepsGoal));
        }
        $data['detail']['steps'] = array_values($t);
        if(count($data['detail']['steps'])==1)
        {
            $data['detail']['steps'] =  $data['detail']['steps']['0'];
        }
        return $data;
    }

    /*
     * 下级部门
     * user_info 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */

    public function getElementPage_childDepartment($data,$params,$user_info,$company_id){
        $parent_id = $this->getFromParams($params,'parent_id',0);
        $department_data = (new DepartmentService())->getDepartmentListByParent($user_info['data']['company_id'],$parent_id);
        $data['detail']['child_department'] = $department_data;
        return $data;
    }
    /*
    * 公司下正在进行的活动列表
    * user_info 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_userMonthlyActivities($data,$params,$user_info,$company_id){
        $month = $this->getFromParams($params,'month',date('m',time()));
         if(!$month)
         {
             $month = date('m',time());
         }
        if(strlen($month)==1)
        {
            $month = '0'.$month;
        }
        $app_id = $this->getFromParams($params,'app_id',101);
        $activity_list = (new ActivityService())->getMonthlyActivityList($app_id,$company_id,$month,$this->getFromParams($params,'app_type','h5'));
        $data['detail']['user_monthly_activities'] = $activity_list;
        return $data;
    }
    /*
    * 部门健步走达成率
    * user_info 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_departmentStepsAchiveRate($data,$params,$user_info,$company_id){
        $userService = new UserService();
        $company_info = (new CompanyService())->getCompanyInfo($company_id);
        if(isset($company_info->detail))
        {
            $company_info->detail = json_decode($company_info->detail,true);
        }
        $dailyStep = $company_info->detail['daily_step']??$this->config->steps->defaultDailyStep;
        $currentTime = time();
        $currentDate = date("Y-m-d",$currentTime);
        $currentDateRange = (new StepsService())->getStepsDateRange($user_info['data']['company_id'],$currentDate);
        $dataArr = [];
        foreach($currentDateRange['data'] as $key => $dateRange)
        {
            $stepsData = (new StepsService())->getStepsDataByDate($user_info['data']['user_id'],$dateRange,$user_info['data']['company_id'],0,"department_id_1",$this->getFromParams($params, 'page', 1), $this->getFromParams($params, 'pageSize', 100));
            $stepsList = $stepsData['list'];
            foreach($stepsList as $detail) {
                if (isset($dateRange['days']))
                {
                    $detail['days'] = $dateRange['days'];
                }else
                {
                    $detail['days'] = null;
                }
                $dataArr[$detail['department_id_1']]['list'][$key] = $detail;
            }
        }
        $total = ["list"=>[]];
        $total['department_name'] = "全员总达成率";//$company_info->company_name;
        $total['user_count'] = $userService->getUserCountByDepartment($company_info->company_id,0);
        foreach($currentDateRange['data'] as $key => $dateRange)
        {
            $stepsData = (new StepsService())->getStepsDataByDate($user_info['data']['user_id'],$dateRange,$user_info['data']['company_id'],0,"",$this->getFromParams($params, 'page', 1), $this->getFromParams($params, 'pageSize', 100));
            $stepsList = $stepsData['list'];
            if(count($stepsList)>=1)
            {
                $total['list'][$key] = $stepsList['0'];
                if (isset($dateRange['days']))
                {
                    $total['list'][$key]['days'] = $dateRange['days'];
                }else
                {
                    $total['list'][$key]['days'] = null;
                }
            }
            else
            {
                $total['list'][$key] =   ['totalStep'=>0,'total_daily_step'=>0,'achives'=>0,'days'=>$dateRange['days']];
            }
        }
        $dataArr['0'] = $total;
        $departmentList = (new DepartmentService())->getDepartmentListByParent($user_info['data']['company_id'],0);
        foreach($departmentList as $key => $departmentInfo)
        {
            if (!isset($dataArr[$departmentInfo->department_id]))
            {
                foreach ($currentDateRange['data'] as $range => $dateRange)
                {
                    $list[$range] = array_merge(['totalStep' => 0, 'total_daily_step' => 0,'achives'=>0 ], ['days' => $dateRange['days']??null]);
                }
                $dataArr[$departmentInfo->department_id] = ["list" => $list];
            }
            $dataArr[$departmentInfo->department_id]['department_name'] = $departmentInfo->department_name;
            $dataArr[$departmentInfo->department_id]['user_count'] = $userService->getUserCountByDepartment($company_info->company_id, $departmentInfo->department_id);
        }
        //continue;
        foreach($dataArr as $department_id => $Listdata)
        {
            foreach($Listdata['list']  as $dateType=> $detail)
            {
                $detail['goal'] =  $Listdata['user_count']*$dailyStep*$detail['days'];
                $detail['achive_rate'] = sprintf("%10.2f",($detail['goal']==0?0:$detail['totalStep']/$detail['goal'])*100);

                $dataArr[$department_id]['list'][$dateType] = $detail;
            }
        }
        ksort($dataArr);
        $data['detail']['data']= $dataArr;
        $data['detail']['dateRange']= $currentDateRange['dateRange'];
        return $data;
    }

    /*
     * 获取公司部门信息
     */
    public function getElementPage_companyDepartment($data,$params,$user_info,$company_id)
    {
        $user_info = (new UserService())->getUserInfo($user_info['data']['user_id'],'user_id,department_id');
        $department_id = $user_info->department_id;
        $departmentService = new DepartmentService();
        $department = $departmentService->getDepartment($department_id);
        $department_data = (new DepartmentService())->getCompanyDepartment($company_id);
        foreach($department_data as $key => $value)
        {
            if($value['department_id'] == $department['department_id_1'])
            {
                foreach ($value['child'] as $k=>$v)
                {
                    if($v['department_id'] == $department['department_id_2'])
                    {
                        $department_data[$key]['child'][$k]['checked'] = 1;
                    }else
                    {
                        $department_data[$key]['child'][$k]['checked'] = 0;
                    }
                }
                $department_data[$key]['checked'] = 1;
            }else
            {
                foreach ($value['child'] as $k=>$v)
                {
                        $department_data[$key]['child'][$k]['checked'] = 0;
                }
                    $department_data[$key]['checked'] = 0;
            }
        }
        $data['detail']['department'] = $department_data;
        return $data;
    }
    /*
     * 俱乐部精彩回顾列表
     * userinfo 用户信息
     * company_id 公司id
     * data 用户包含的element信息
     * params 页面标识和company_id
     */
    public function getElementPage_hotList($data,$params,$user_info,$company_id){
        $companyInfo = (new CompanyService())->getCompanyInfo($user_info['data']['company_id'],"company_id,detail");
        $companyInfo->detail = json_decode($companyInfo->detail);
        //获取企业指定的精彩回顾列表
        $list_id = $companyInfo->detail->hot??0;
        $data['detail']['list_id'] = $list_id;
        $params['list_id'] = $list_id;
        $params['page_size'] = $this->getFromParams($params,"pageSize",3);
        $data = $this->getElementPage_list($data,$params,$user_info,$company_id);
       // $available = $this->getElementPage_post($data,$params,$user_info,$company_id);
        $available = 1;
        $data['data']['available'] = $available; //$available['detail']['available']['result'];
        return $data;
    }
    /*
     * 联系我们的公众号
     */
    public function getElementPage_ourContact($data,$params,$user_info,$company_id)
    {
        $wechat_acount = (new ConfigService())->getConfig("wechat_account");
        $wechat_acount_name = $wechat_acount->content;
        $data['detail']['wechat_acount_name'] = $wechat_acount_name;
        return $data;
    }
    /*
     * banner
     */
    public function getElementPage_banner($data,$params,$user_info,$company_id)
    {
        $companyInfo = (new CompanyService())->getCompanyInfo($user_info['data']['company_id'],"company_id,detail");
        $companyInfo->detail = json_decode($companyInfo->detail,true);
        $banner_type = $data['detail']['banner_type'];
        $banner_list = ($companyInfo->detail[$banner_type])??[];
        $data['detail'] = $banner_list;
        return $data;
    }
    /*
     * 默认欢迎用户
     */
    public function getElementPage_welcomUser($data,$params,$user_info,$company_id)
    {
        $userImg = (new ConfigService())->getConfig("default_user_img");
        $userImg->content = json_decode($userImg->content,true);
        $userImg = $userImg->content['0']['img_url']??"";
        $data['detail'] = ['user_img'=>$userImg,'true_name'=>"欢迎您",'nick_name'=>"欢迎您"];
        return $data;
    }
    /*
     * 获取公司下所有的活动
     *
     */
    public function getElementPage_companyActivityList($data,$params,$user_info,$company_id)
    {
        $app_id = $this->getFromParams($params,'app_id',101);
        //用户当前位置
        $current_lat = $this->getFromParams($params,'latitude',0);
        $current_lng = $this->getFromParams($params,'longitude',0);

        //分页参数
        $page = $this->getFromParams($params,'page',1);
        $pageSize = $this->getFromParams($params,'pageSize',3);
        //公司活动列表
        $activityList = (new ActivityService())->getActivityListByCompany(1,'activity_id,status,activity_name,start_time,system,app_id,comment,start_time,end_time,apply_start_time,apply_end_time,detail,create_time',$club_id = -1);
        $company_info = (new CompanyService())->getCompanyInfo($company_id,'company_id,company_name,detail');

        //去除文体汇的活动和无效的活动 和非本app活动
        foreach ($activityList as $key =>$activity_info)
        {
            if($activity_info->system == 1 || $activity_info->status == 0 || $activity_info->app_id != $app_id)
            {
              unset($activityList[$key]);
              continue;
            }
        }

         $activityList =  json_decode(json_encode($activityList),true);
         //按状态排序
         $activityList = (new ActivityService())->activitySort($activityList);

         //分页
         $offset = ($page-1)*$pageSize;
         if($offset+$pageSize >= count($activityList))
         {
             $residuals = 0;
         }else
         {
             $residuals = 1;
         }
        $activityList = array_slice($activityList,$offset,$pageSize);
         //处理需要的数据 经纬度和公司信息 头图等
         foreach ($activityList as $key=>$activity_info)
         {
             $activityList[$key]['company_name'] = $company_info->company_name;
             $detail = $activity_info['detail'];
             $activtiy_lat = $detail['checkin']['latitude']??0;
             $activity_lng = $detail['checkin']['longitude']??0;

             //取出活动地点的经纬度
             $distance = Common::getDistance($current_lat,$current_lng,$activtiy_lat,$activity_lng);
             //校验距离 小于3000米的活动位置在地图显示 否则不显示
             $is_show = $distance < 3000 ?1:0;
             $activityList[$key]['latitude'] = $activtiy_lat;
             $activityList[$key]['longitude'] = $activity_lng;
             $activityList[$key]['is_show'] = $is_show;
             //活动头图
             $header_image = $detail['header_image']??'';
             $activityList[$key]['header_image'] = $header_image;
             //头图不存在取默认图片
             if(!$header_image)
             {
                 $detail = json_decode($company_info->detail,true);
                 $bannerList = [];
                 //需默认Banner
                 if(isset($detail['clubBanner']))
                 {
                     $bannerList = $detail['clubBanner'];
                 }
                 $default_header_image = $bannerList[0]['img_url'];
             }else
             {
                 $default_header_image = '';
             }
             $activityList[$key]['defalut_header_image'] = $default_header_image;
             unset($activityList[$key]['detail']);

             // 用户是否已报名
             $res = (new ActivityService())->checkUserActivity($user_info['data']['user_id'],$activity_info['activity_id']);
             $activityList[$key]['applied'] = isset($res->id)?1:0;

             //活动人员列表
             $member_list = (new ActivityService())->getActivityMemberList($activity_info['activity_id']);
             $activity_member_list = [];
             foreach ($member_list as $value)
             {
                 $userInfo = (new UserService())->getUserInfo($value->user_id,'user_id,nick_name,true_name,user_img');
                 $activity_member_list[] = $userInfo;
             }
             $activityList[$key]['activity_member_list'] = $activity_member_list;
             $activityList[$key]['user_count'] = (new ActivityService())->getActivityMemberCount($activity_info['activity_id']);
             //日期格式
             $month = date("m月",strtotime($activity_info['start_time']));
             $day = date("d",strtotime($activity_info['start_time']));
             if(date('Y',strtotime($activity_info['start_time'])) != date('Y',strtotime($activity_info['end_time'])))
             {  //跨年加上年份
                $chinese_format_time = date('Y年m月d日 H:i',strtotime($activity_info['start_time'])).'-'.date('Y年m月d日 H:i',strtotime($activity_info['end_time']));
             }elseif(date('m-d',strtotime($activity_info['start_time'])) != date('m-d',strtotime($activity_info['end_time']))){
                 //跨天的加上月份
                 $chinese_format_time = date('m月d日 H:i',strtotime($activity_info['start_time'])).'-'.date('m月d日 H:i',strtotime($activity_info['end_time']));
             }else
             {
                 $chinese_format_time = date('H:i',strtotime($activity_info['start_time'])).'-'.date('H:i',strtotime($activity_info['end_time']));
             }
             $activityList[$key]['start_month'] = $month;
             $activityList[$key]['start_day'] = $day;
             $activityList[$key]['chinese_format_time'] = $chinese_format_time;
         }

         $data['detail']['activity_list'] = $activityList;
         $data['detail']['residuals'] = $residuals;
         return $data;
    }
}