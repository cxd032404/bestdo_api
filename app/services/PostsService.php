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
}