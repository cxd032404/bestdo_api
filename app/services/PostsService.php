<?php
use HJ\Page;
use HJ\PageElement;
class PostsService extends BaseService
{
	private $msg = 'success';

	//提交文章
    //list_id：列表ID
    //uploadedFiles：已经上传的资源
	public function addPosts($list_id,$detail,$uploadedFiles)
    {
        //获取列表信息
        $listInfo = (new ListService())->getListInfo(intval($list_id));
        //unset($listInfo);
        if(!isset($listInfo['list_id']))
        {
            $return = ['result'=>false,'data'=>['msg'=>"文章列表不存在"]];
        }
        else
        {
            if(strlen(trim(htmlspecialchars($detail['comment'])))<=3)
            {
                $return = ['result'=>false,'data'=>['msg'=>"输入的内容有点少哦"]];
            }
            else
            {
                //初始化数据
                $postInfo = new \HJ\Posts();
                $postInfo->list_id = $listInfo['list_id'];
                $postInfo->company_id = $listInfo['company_id'];
                $postInfo->content = trim(htmlspecialchars($detail['comment']));
                $postInfo->source = json_encode($uploadedFiles);
                $postInfo->create_time = $postInfo->update_time = date("Y-m-d H:i:s");
                $postInfo->user_id = 1;
                $create = $postInfo->create();
                if($create)
                {
                    $return = ['result'=>true,'data'=>['post_id'=>$postInfo->post_id]];
                }
                else
                {
                    $return = ['result'=>false,'msg'=>"发布失败"];
                }
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
    public function getPosts($list_id,$columns = "*",$order = "post_id DESC",$page = 1,$pageSize =2)
    {
        $params =             [
            "list_id = ".$list_id,
            "columns" => $columns,
            "order" => $order,
            "limit" => ["offset"=>($page-1)*$pageSize,"number"=>$pageSize]
        ];
        $params_count = [
            "list_id = ".$list_id,
            "columns" => "count(1) as count",
        ];
        $list = (new \HJ\Posts())->find($params);
        $count = (new \HJ\Posts())->findFirst($params_count)['count']??0;
        $return  = ['data'=>$list,
        'count'=>$count,'total_page'=>ceil($count/$pageSize)];

        return $return;
    }
}