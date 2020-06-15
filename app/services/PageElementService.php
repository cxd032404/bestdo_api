<?php


class PageElementService extends BaseService
{
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
            $activitylog_info = (new UserService())->getActivityLogByUser($user_info['data']['user_id'],$listInfo->activity_id);
            if(!$activitylog_info)
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
            $activitylog_info = (new UserService())->getActivityLogByUser($user_info['data']['user_id'],$data['detail']['activity_id']);
            if(!$activitylog_info)
            {
                $data['detail']['applied'] = 0;
            }
            else
            {
                $data['detail']['applied'] = 1;
                //unset($pageElementList[$key]);
            }
        }
        return $data;
    }

    /*
    * 创建活动入口
    * userinfo 用户信息
    * company_id 公司id
    * data 用户包含的element信息
    * params 页面标识和company_id
    */
    public function getElementPage_activityCreate($data,$params,$user_info,$company_id){
        $userClubListWithPermission = (new ClubService())->getUserClubListWithPermission($user_info['data']['user_id']);

//        $userClubList = (new ClubService())->getUserClubList($user_info['data']['user_id'],"member_id,club_id,permission");

        $data['user_club_list'] = $userClubListWithPermission;
        $data['member_limit'] = [100=>"100人",10=>"10人",3=>"3人"];
        $data['monthly_apply_limit'] = [1=>"1次",2=>"2次",3=>"3次"];
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


}