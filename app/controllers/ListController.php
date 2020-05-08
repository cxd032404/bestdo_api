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
use AliyunService;

class ListController extends BaseController
{

	public function postAction( )
	{
	    $upload = (new UploadService())->getUploadedFile(['upload_img.1','upload_txt'],[],0,0);
	    $listInfo  = (new ListService())->getListInfo(intval($this->request->getPost('list_id')));
	    $post = (new PostsService())->addPosts(intval($this->request->getPost('list_id')),$this->request->getPost('detail'),$upload);
        if($post['result'])
        {
            return $this->success($post['data']);
        }
        else
        {
            return $this->failure([],$post['data']['msg']);
        }
    }

}
