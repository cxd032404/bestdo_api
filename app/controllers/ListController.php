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
        $visible = intval($this->request->getPost('visible')??0);
        $list_id = intval($this->request->getPost('list_id')??0);
        $post_id = intval($this->request->getPost('post_id')??0);
        if($post_id > 0)
        {
            $post = (new PostsService())->updatePosts(intval($this->request->getPost('post_id')),$tokenInfo['data']['user_info']->user_id,$this->request->getPost('detail'),$visible);
        }
        else
        {
            $listInfo  = (new ListService())->getListInfo(intval($this->request->getPost('list_id')));
            $post = (new PostsService())->addPosts(intval($this->request->getPost('list_id')),$tokenInfo['data']['user_info']->user_id,$this->request->getPost('detail'),$visible);
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
        $referer = $this->request->getHTTPReferer();
        if(strpos($referer,"admin."))
        {
            $this->redirect($referer);
        }
        $post_id = intval($this->request->getQuery('post_id')??0);
        $remove = ['result'=>false,'data'=>['msg'=>"文章不存在"]];
        if($post_id > 0)
        {
            $remove = (new PostsService())->removeSource(intval($post_id),trim($this->request->getQuery('sid')??""));
        }
        if($remove['result'])
        {
            if(strpos($referer,"admin."))
            {
                $this->redirect($referer);
            }
            return $this->success($remove['data']);
        }
        else
        {
            if(strpos($referer,"admin."))
            {
                $this->redirect($referer);
            }
            return $this->failure([],$remove['data']['msg']);
        }
    }


}
