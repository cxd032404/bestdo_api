<?php


class PageElementService extends BaseService
{

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
            $userList = UserInfo::find([
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
            /*
            $postskudos_info = PostsKudos::findFirst([
                "sender_id=:sender_id: and post_id=:post_id: and is_del=0 and create_time between :starttime: AND :endtime: ",
                'bind'=>[
                    'sender_id'=>$user_info['data']['user_id']??0,
                    'post_id'=>$data['data']['data'][$k]->post_id,
                    'starttime'=>date('Y-m-d').' 00:00:00',
                    'endtime'=>date('Y-m-d').' 23:59:59',
                ]
            ]);
            */
            $postskudos_info = (new PostsService())->checkKudos($user_info['data']['user_id']??0,"",$data['data']['data'][$k]->post_id);
            $data['data']['data'][$k]->is_kudos = 0;
            if(isset($postskudos_info->id)){
                $data['data']['data'][$k]->is_kudos = 1;
            }
        }
        return $data;

    }

    public function getElementPage_activityLog($data,$params,$user_info){
        $post_id = $this->getFromParams($params,"post_id","");
        if($post_id){
            $post_list = $post_id;
        }else{
            $post_list = [];
            $activity_log = UserActivityLog::find([
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