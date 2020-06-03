<?php
use HJ\Page;
use HJ\PageElement;
class PostsService extends BaseService
{
	private $msg = 'success';

	//提交文章
    //list_id：列表ID
    //uploadedFiles：已经上传的资源
	public function addPosts($list_id,$user_id,$detail,$visible)
    {
        $oUpload = new UploadService();
        //获取列表信息
        $listInfo = (new ListService())->getListInfo($list_id,"list_id,company_id,detail")->toArray();
        $listInfo['detail'] = json_decode($listInfo['detail'],true);
        if(!isset($listInfo['list_id']))
        {
            $return = ['result'=>false,'data'=>['msg'=>"文章列表不存在"]];
        }
        else
        {

            //计算可用的文件数量
            $count = $oUpload->getAvailableSourceCount([],$listInfo['detail']);
            $upload = $oUpload->getUploadedFile([],[],0,0,$count);
            if(isset($upload['name']))
            {
                $return = ['result'=>false,'data'=>['msg'=>"您所发布的".$upload['name']."数量已经超过限制，请重新提交"]];
            }
            else
            {
                //初始化数据
                $postInfo = new \HJ\Posts();
                if($visible>0){
                    $postInfo->visible = 1;
                }
                $postInfo->list_id = $listInfo['list_id'];
                $postInfo->title = trim($detail['title']);
                $postInfo->company_id = $listInfo['company_id'];
                $postInfo->content = trim($detail['comment']);
                $postInfo->source = json_encode($upload);
                $postInfo->create_time = $postInfo->update_time = date("Y-m-d H:i:s");
                $postInfo->user_id = $user_id;
                $create = $postInfo->create();
                if($create)
                {
                    $return = ['result'=>true,'data'=>['post_id'=>$postInfo->post_id,'new_key'=>array_keys($upload)]];
                }
                else
                {
                    $return = ['result'=>false,'msg'=>"发布失败"];
                }
            }
        }
        return $return;
    }
    //提交更新文章
    //list_id：文章ID
    //uploadedFiles：已经上传的资源
    public function updatePosts($post_id,$user_id,$manager_id,$detail,$visible)
    {
        $oUpload = new UploadService();
        //获取列表信息
        $postInfo = self::getPosts(intval($post_id),"post_id,user_id,list_id,content,source,update_time")->toArray();
        if(!isset($postInfo['post_id']))
        {
            $return = ['result'=>false,'data'=>['msg'=>"文章不存在"]];
        }
        else
        {
            if($postInfo['user_id']!=$user_id && $manager_id == 0)
            {
                $return = ['result'=>false,'data'=>['msg'=>"文章作者不匹配"]];
            }else{
                //获取列表信息
                $listInfo = (new ListService())->getListInfo($postInfo['list_id'],"list_id,detail")->toArray();
                $listInfo['detail'] = json_decode($listInfo['detail'],true);
                $postInfo['source'] = json_decode($postInfo['source'],true);
                //计算可用的文件数量
                $count = $oUpload->getAvailableSourceCount($postInfo['source'],$listInfo['detail']);
                $upload = $oUpload->getUploadedFile([],[],0,0,$count);
                //如果返回类型名称
                if(isset($upload['name']))
                {
                    $return = ['result'=>false,'data'=>['msg'=>"您所发布的".$upload['name']."数量已经超过限制，请重新提交"]];
                }
                else
                {
                    foreach($upload as $name => $file)
                    {
                        $postInfo['source'][$name.count($upload)] = $file;
                    }
                    if($visible>0){
                        $postInfo['visible'] = 1;
                    }
                    $postInfo['source'] = $oUpload->sortUpload($postInfo['source']);
                    //查询当前提交的文件key值
                    $new_add = [];
                    foreach($upload as $name => $file)
                    {
                        foreach(array_reverse($postInfo['source']) as $key => $file_2)
                        {
                            if($file == $file_2)
                            {
                                $new_add[] = $key;
                                break;
                            }
                        }
                    }
                    $postInfo['title'] = trim($detail['title']);
                    $postInfo['source'] = json_encode($postInfo['source']);
                    $postInfo['content'] = trim($detail['comment']);
                    $postInfo['update_time'] = date("Y-m-d H:i:s");
                    $data = json_decode(json_encode($postInfo),true);
                    $update = self::updatePost($postInfo['post_id'],$data);
                    if($update)
                    {
                        $return = ['result'=>true,'data'=>['post_id'=>$postInfo['post_id'],'new_key'=>$new_add]];
                    }
                    else
                    {
                        $return = ['result'=>false,'data'=>['msg'=>"发布失败"]];
                    }
                }
            }
        }
        return $return;
    }
    //提交更新文章 移除部分资源
    //list_id：文章ID
    //uploadedFiles：已经上传的资源
    public function removeSource($post_id,$sid)
    {
        //获取列表信息
        $postInfo = self::getPosts(intval($post_id),"post_id,source,update_time")->toArray();
        if(!isset($postInfo['post_id']))
        {
            $return = ['result'=>false,'data'=>['msg'=>"文章不存在"]];
        }
        else
        {
            $postInfo['source'] = json_decode($postInfo['source'],true);
            if(!isset($postInfo['source'][$sid]))
            {
                $return = ['result'=>true,'data'=>['post_id'=>$postInfo['post_id']]];
            }
            else
            {
                print_r($postInfo);
                unset($postInfo['source'][$sid]);
                $source = (new UploadService())->sortUpload($postInfo['source']);
                $sid = explode(".",$sid);
                $t = [];
                foreach($postInfo['source'] as $k => $file)
                {
                    if(substr($k,0,strlen($sid['0'])+1)==$sid['0'].".")
                    {
                        $t[$sid['0'].".".(count($t)+1)] = $file;
                        unset($postInfo['source'][$k]);
                    }
                }
                foreach($t as $k => $file)
                {
                    $postInfo['source'][$k] = $file;
                }
                $postInfo['source'] = json_encode($postInfo['source']);
                $postInfo['update_time'] = date("Y-m-d H:i:s");
                $update = self::updatePost($postInfo['post_id'],$postInfo);
                if($update)
                {
                    $return = ['result'=>true,'data'=>['post_id'=>$postInfo['post_id']]];
                }
                else
                {
                    $return = ['result'=>false,'msg'=>"删除失败"];
                }
            }
        }
        return $return;
    }
    //更新文章的隐藏状态
    //post_id：文章ID
    //display：目标显示状态
    public function updateDisplay($post_id,$display)
    {
        //获取列表信息
        $postInfo = self::getPosts(intval($post_id),"post_id,list_id,visible,views,update_time")->toArray();
        if(!isset($postInfo['post_id']))
        {
            $return = ['result'=>false,'data'=>['msg'=>"文章不存在"]];
        }
        else
        {
            $postInfo['visible'] = $display;
            $postInfo['update_time'] = date("Y-m-d H:i:s");
            $update = self::updatePost($postInfo['post_id'],$postInfo);
            if($update)
            {
                $return = ['result'=>true,'data'=>['post_id'=>$postInfo['post_id']]];
            }
            else
            {
                $return = ['result'=>false,'msg'=>"更改隐藏状态失败"];
            }
        }
        return $return;
    }
    //根据列表ID获取列表
    //$list_id：列表ID
    //cloumns：数据库的字段列表
    //order：排序
    //page:页码
    //pageSize：单页数量
    public function getPostsListOld($list_id,$user_id,$columns = "*",$order = "post_id DESC",$start = 0,$page = 1,$pageSize =4)
    {

        if(is_array($list_id))
        {
            $params =             [
                ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id in (".implode(",",$list_id).")"." ".($start>0?(" and post_id <".$start):"")." and visible=1",
                "columns" => 'post_id',
                "order" => $order,
                "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
            ];
            $params_count = [
                ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id in (".implode(",",$list_id).")"." and visible=1",
                "columns" => "count(1) as count",
            ];
        }
        else
        {
            $params =             [
                ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id = '".$list_id."'"." ".($start>0?(" and post_id <".$start):"")." and visible=1" ,
                "columns" => 'post_id',
                "order" => $order,
                "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
            ];
            $params_count = [
                ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id = '".$list_id."'"." and visible=1",
                "columns" => "count(1) as count",
            ];
        }
        $list = (new \HJ\Posts())->find($params)->toArray();
        $count = (new \HJ\Posts())->findFirst($params_count)['count']??0;
        $return  = ['data'=>$list,
        'count'=>$count,'total_page'=>ceil($count/$pageSize)];
        return $return;
    }

    /*
     * 分页缓存方法 new
     */

    public function getPostsList($list_id,$user_id,$columns = "*",$order = "post_id DESC",$start = 0,$page = 1,$pageSize =4)
    {
        $user_ids =':';
        sort($user_id);

        foreach ($user_id as $value)
        {
                $user_ids .='-'.$value;
        }

        $cacheName = 'user_post:list_id:'.$list_id.':user_id'.$user_ids.':order:'.$order.':start:'.$start.':page:'.$page.':pageSize:'.$pageSize;
        $posts_id = $this->redis->get($cacheName);
        //判断是否有缓存
        if($posts_id)
        {
            //缓存
          $list = json_decode($posts_id);
        }
        else
        {
            //读库
            if(is_array($list_id))
            {
                $params = [
                    ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id in (".implode(",",$list_id).")"." ".($start>0?(" and post_id <".$start):"")." and visible=1",
                    "columns" => 'post_id',
                    "order" => $order,
                    "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
                ];
            }
            else
            {
                $params = [
                    ($user_id?("user_id  in (".implode(",",$user_id).") and "):"")."list_id = '".$list_id."'"." ".($start>0?(" and post_id <".$start):"")." and visible=1" ,
                    "columns" => 'post_id',
                    "order" => $order,
                    "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
                ];
            }
            $list = (new \HJ\Posts())->find($params);
            $this->redis->set($cacheName,json_encode($list));
            $this->redis->expire($cacheName,60);
        }
        $return = [];
        foreach($list as $key  => $value)
        {
            $postData = $this->getPosts($value->post_id,$columns);
            $return['data'][$key] = $postData;
        }
        return $return;
    }



    public function getPosts($post_id,$columns = "post_id",$cache = 1)
    {
        $cacheName = 'user_posts_'.$post_id;
        $params =             [
            "post_id = ".$post_id,
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
                $posts = (new \HJ\Posts())->findFirst($params);
                if(isset($posts->post_id)) {
                    $this->redis->set($cacheName, json_encode($posts));
                    $this->redis->expire($cacheName, 3600);
                } else
                {
                    $posts = [];
                }
            }
        }
        else
        {
            $posts = (new \HJ\Posts())->findFirst($params);
            if(isset($posts->post_id))
            {
                $this->redis->set($cacheName,json_encode($posts));
                $this->redis->expire($cacheName,3600);
            }else
                {
                    $posts = [];
            }
        }

        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($posts as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($posts->$key);
                }
            }
        }
        if(isset($posts->user_id))
        {
            $posts->userInfo = (new UserService())->getUserInfo($posts->user_id);
        }

     return $posts;
    }

    public function updatePost($post_id,$updateData)
    {
        $posts = (new \HJ\Posts())->findFirst(['post_id = '.$post_id]);
        foreach($updateData as $key => $value)
        {
            $posts->$key = $value;
        }
        $return = $posts->save();
        if($return)
        {
            $this->getPosts($post_id,'*',0);
        }
        return $return;
    }
    public function updatePostView($post_id)
    {
        $posts = (new \HJ\Posts())->findFirst(['post_id = '.$post_id]);
        if($posts)
        {
            $posts->views = $posts->views + 1;
            return $posts->save();
        }
        else
        {
            return true;
        }
    }
}