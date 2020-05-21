<?php
use HJ\Page;
use HJ\PageElement;
class PageService extends BaseService
{
	private $msg = 'success';

    //根据页面标示获取页面信息
    //$page_sign：页面标示
    public function getPageInfo($company_id,$page_sign,$params = "",$user_info)
	{
	    //获取页面信息
	    $pageInfo = $this->getPageBySign($company_id,$page_sign);
	    //没如果获取到
	    if(!$pageInfo)
        {
            $return  = ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
	    else
        {
            $params = json_decode($params,true);
            //转数组
            $pageInfo = $pageInfo->toArray();
            //获取页面元素详情
	        $pageElementList  = $this->getPageElementByPage($pageInfo['page_id'],"element_id,element_name,element_sign,element_type,detail",$params['element_sign_list']??[])->toArray();
	        foreach($pageElementList as $key => $elementDetail)
            {
                //数组解包
                $pageElementList[$key]['detail'] = json_decode($elementDetail['detail'],true);
                //列表
                if($elementDetail['element_type'] == "list")
                {
                    //指定数据
                    if(isset($pageElementList[$key]['detail']['list_id']))
                    {
                        $list_id = $pageElementList[$key]['detail']['list_id'];
                    }
                    else//页面获取
                    {
                        $list_id = $this->getFromParams($params,$pageElementList[$key]['detail']['from_params'],0);
                    }
                    //获取列表
                    $listInfo = (new ListService())->getListInfo($list_id,"list_id,list_type");
                    //获取符合查询条件用户
                    $search_content = $this->getFromParams($params,"search_content","");
                    $userList = [];
                    if($search_content){
                        $userList = UserInfo::find([
                            "nick_name like '%".$search_content."%' and is_del=1 and company_id='".$company_id."'",
                            'columns'=>'user_id',
                            'order'=>'user_id desc'
                        ])->toArray();
                        $userList = array_column($userList,'user_id');
                    }
                    $pageElementList[$key]['data'] = (new PostsService())->getPostsList($listInfo['list_id'],$userList,"*","post_id DESC",$this->getFromParams($params,"start",0),$this->getFromParams($params,"page",1),$this->getFromParams($params,"page_size",3));
                    foreach($pageElementList[$key]['data']['data'] as $k => $postDetail)
                    {
                        $pageElementList[$key]['data']['data'][$k]['source'] = json_decode($postDetail['source'],true);
                        $pageElementList[$key]['data']['data'][$k]['source'] = (new UploadService())->parthSource($pageElementList[$key]['data']['data'][$k]['source']);
                        $pageElementList[$key]['data']['data'][$k]['list_type'] = $listInfo['list_type'];
                        //获取列表作者信息 
                        $userInfo = UserInfo::findFirst([
                            "user_id='".$pageElementList[$key]['data']['data'][$k]['user_id']."' and is_del=1",
                            'columns'=>'nick_name,true_name,user_img',
                            'order'=>'user_id desc'
                        ]);
                        $pageElementList[$key]['data']['data'][$k]['user_info'] = $userInfo;
                        //获取用户今日是否可以点赞
                        $postskudos_info = PostsKudos::findFirst([
                            "sender_id=:sender_id: and post_id=:post_id: and is_del=1 and create_time between :starttime: AND :endtime: ",
                            'bind'=>[
                                'sender_id'=>$user_info['data']['user_id'],
                                'post_id'=>$pageElementList[$key]['data']['data'][$k]['post_id'],
                                'starttime'=>date('Y-m-d').' 00:00:00',
                                'endtime'=>date('Y-m-d').' 23:59:59',
                            ]
                        ]);
                        $pageElementList[$key]['data']['data'][$k]['is_kudos'] = 0;
                        if(isset($postskudos_info->id)){
                            $pageElementList[$key]['data']['data'][$k]['is_kudos'] = 1;
                        }
                    }
                }
                elseif($elementDetail['element_type'] == "slideNavi")
                {
                    if($pageElementList[$key]['detail']['source_from']=="from_vote")
                    {
                        $voteInfo = (new VoteService())->getVote($pageElementList[$key]['detail']['vote_id'])->toArray();
                        $voteInfo['detail'] = json_decode($voteInfo['detail'],true);
                        $pageElementList[$key]['detail']['vote_option'] = $voteInfo['detail'];
                    }
                    if(isset($pageElementList[$key]['detail']['jump_urls']))
                    {
                        //滑动导航数据转化
                        $navList = [];
                        foreach ($pageElementList[$key]['detail']['jump_urls'] as $navkey=>$nacvalue) {
                            $data['name'] = $navkey;
                            $nav_type = explode('#',str_replace('"', '', $nacvalue));
                            $data['url'] = reset($nav_type);
                            $data['type'] = explode('.',str_replace('"', '', end($nav_type)));
                            $navList[] = $data;
                        }
                        $pageElementList[$key]['detail']['jump_urls'] = $navList;
                    }

                }
                elseif($elementDetail['element_type'] == "companyInfo")
                {
                    $pageElementList[$key]['detail'] = (new CompanyService())->getCompanyInfo($company_id)->toArray();
                }
                elseif($elementDetail['element_type'] == "userInfo")
                {
                    $pageElementList[$key]['detail']['user_info'] = $user_info;
                }
                elseif($elementDetail['element_type'] == "post")
                {
                    $pageElementList[$key]['detail']['available'] = 1;

                    if(isset($pageElementList[$key]['detail']['list_id']))
                    {
                        $list_id = $pageElementList[$key]['detail']['list_id'];
                    }
                    else
                    {
                        $list_id = $this->getFromParams($params,$pageElementList[$key]['detail']['from_params'],0);
                    }
                    $pageElementList[$key]['detail']['available'] = 1;
                    //获取列表信息
                    $listInfo = (new ListService())->getListInfo($list_id,"list_id,activity_id,detail")->toArray();
                    //数据解包
                    $listInfo['detail'] = json_decode($listInfo['detail'],true);
                    $postExists = (new PostsService())->getPostsList($list_id,[$user_info['data']['user_id']],"post_id","post_id DESC",0,1,1);
                    //已经提交过
                    if($postExists['count']>0)
                    {
                        $pageElementList[$key]['detail']['available'] = 0;
                    }
                    $afterActions = (new ListService())->processAfterPostAction($listInfo['list_id'],$user_info['data']['user_id'],$listInfo['detail']);
                    $pageElementList[$key]['detail']['after_action'] = $afterActions;
                }
                elseif($elementDetail['element_type'] == "postsDetail")
                {
                    $post_id = $this->getFromParams($params,$pageElementList[$key]['detail']['from_params'],0);
                    $postsInfo = (new PostsService())->getPosts($post_id,"post_id,user_id,content,source,views,kudos,create_time,update_time");
                    if($postsInfo)
                    {
                        $postsInfo = $postsInfo->toArray();
                        $postsInfo['source'] = json_decode($postsInfo['source'],true);
                        $postsInfo['source'] = (new UploadService())->parthSource($postsInfo['source']);
                        //是否可以修改
                        $postsInfo['editable'] = 0;
//                        if($postsInfo['user_id']==$user_info['data']['user_id'] && strtotime($postsInfo['create_time'])>time()-1800){
//                            $postsInfo['editable'] = 1;
//                        }
                        $pageElementList[$key]['detail'] = $postsInfo;
                    }

                }
                elseif($elementDetail['element_type'] == "rankByKudos")
                {
                    //指定数据
                    if(isset($pageElementList[$key]['detail']['list_id']))
                    {
                        $list_id = $pageElementList[$key]['detail']['list_id'];
                    }
                    else//页面获取
                    {
                        $list_id = $this->getFromParams($params,$pageElementList[$key]['detail']['from_params'],0);
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
                        $userinfo = UserInfo::findFirst([
                            "user_id = '".$p_val['user_id']."'",
                            "columns"=>"user_id,nick_name,true_name,user_img,company_id"
                        ]);
                        if(isset($userinfo->user_id) && $userinfo->user_id==$user_info['data']['user_id']){
                            $self['user_id'] = $userinfo->user_id??"";
                            $self['nick_name'] = $userinfo->nick_name??"";
                            $self['true_name'] = $userinfo->true_name??"";
                            $self['user_img'] = $userinfo->user_img??"";
                            $self['company_id'] = $userinfo->company_id??"";
                            $self['count'] = $p_val['count']??0;
                        }
                        $posts[$p_key]['nick_name'] = (isset($userinfo->user_id))?$userinfo->nick_name:"";
                        $posts[$p_key]['true_name'] = (isset($userinfo->user_id))?$userinfo->true_name:"";
                        $posts[$p_key]['user_img'] = (isset($userinfo->user_id))?$userinfo->user_img:"";
                        $posts[$p_key]['company_id'] = (isset($userinfo->user_id))?$userinfo->company_id:"";
                    }
                    $pageElementList[$key]['detail']['self'] = $self??[];
                    $pageElementList[$key]['detail']['all'] = $posts;
                }
            }
	        $pageElementList = array_combine(array_column($pageElementList,'element_sign'),array_values($pageElementList));
            $return = ['result'=>1,'code'=>200,'data'=>['pageInfo'=>$pageInfo,'pageElementList'=>$pageElementList]];
        }
        return $return;
	}
    //根据页面标识获取页面
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getPageBySign($company_id,$page_sign,$columns = "page_id,page_name")
    {
        $params =             [
            "page_sign = '$page_sign' and company_id = '$company_id'",
            "columns" => $columns
        ];
        return (new Page())->findFirst($params);
    }
	//根据页面ID获取元素列表
    //$page_id：页面ID
    //cloumns：数据库的字段列表
    //order：排序
	public function getPageElementByPage($page_id,$columns = "element_id,element_type",$element_sign_list = ["pic_2"],$order = "element_type DESC")
    {
        $params =             [
            //"page_id = ".$page_id,
            "columns" => $columns,
            "order" => $order,
            "bind" => ["elementSignList"=>$element_sign_list]
        ];
        if(count($element_sign_list))
        {
            $params[] = "page_id = $page_id and element_sign IN ({elementSignList:array})";
        }
        else
        {
            $params[] = "page_id = ".$page_id;
        }
        return (new \HJ\PageElement())->find(
            $params
        );
    }
    //检查页面参数是否完整和类型正确
    //$params:页面参数json串
    public function checkPageParams($params,$company,$page_sign)
    {
        //获取页面信息
        $pageInfo = $this->getPageBySign($company,$page_sign,'page_id,detail');
        if($pageInfo)
        {
            $pageInfo = $pageInfo->toArray();
        }
        else
        {
            return ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
        $pageInfo['detail'] = json_decode($pageInfo['detail'],true);
        if(isset($pageInfo['detail']['params']) && count($pageInfo['detail']['params'])>0)
        {
            $params = json_decode($params,true);
            $return = ['result'=>1,'detail'=>['lack'=>[],'error'=>[]]];
            foreach($pageInfo['detail']['params'] as $paramsInfo)
            {
                if(!isset($params[$paramsInfo['name']]))
                {
                    $return['result'] = 0;
                    $return['code'] = 500;
                    $return['detail']['lack'][] = $paramsInfo['name'];
                }
                else
                {
                    if(in_array($paramsInfo['type'],['int']))
                    {
                        $function_name  = "is_".$paramsInfo['type'];
                        if(!$function_name($params[$paramsInfo['name']]))
                        {
                            $return['result'] = 0;
                            $return['code'] = 500;
                            $return['detail']['error'][] = $paramsInfo['name'];
                        }
                    }
                }
            }
        }
        else
        {
            $return  = ['result'=>1];

        }
        return $return;
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