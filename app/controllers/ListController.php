<?php
// +----------------------------------------------------------------------
// | API控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     api.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;

class ListController extends BaseController
{

	public function postAction()
	{
        //调用user_token解密方法
        $tokenInfo  = (new UserService)->getDecrypt();
        //返回值判断
        if($tokenInfo['result']!=1){
            return $this->failure(['jump_url'=>'/login'],$tokenInfo['msg'],$tokenInfo['code']);
        }
	    $upload = (new UploadService())->getUploadedFile([],[],0,0);
	    //$upload = [];
        $list_id = intval($this->request->getPost('list_id')??0);
        $post_id = intval($this->request->getPost('post_id')??0);
        if($post_id > 0)
        {
            $post = (new PostsService())->updatePosts(intval($this->request->getPost('post_id')),$this->request->getPost('detail'));
        }
        else
        {
            $listInfo  = (new ListService())->getListInfo(intval($this->request->getPost('list_id')));
            $post = (new PostsService())->addPosts(intval($this->request->getPost('list_id')),$tokenInfo['data']['user_info']->user_id,$this->request->getPost('detail'),$upload);
        }
        if($post['result'])
        {
            return $this->success($post['data']);
        }
        else
        {
            return $this->failure([],$post['data']['msg']);
        }
    }
    public function source_removeAction()
    {
        $post_id = intval($this->request->getQuery('post_id')??0);
        if($post_id > 0)
        {
            $remove = (new PostsService())->removeSource(intval($post_id),trim($this->request->getQuery('sid')??""));
        }
        if($remove['result'])
        {
            return $this->success($remove['data']);
        }
        else
        {
            return $this->failure([],$remove['data']['msg']);
        }
    }

}
