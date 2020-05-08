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
        echo "here";
	    if ($this->request->hasFiles() == true) {
            $uploadedFile = [];
            foreach ($this->request->getUploadedFiles() as $file){
                $target = ROOT_PATH.'/upload/'.$file->getName();
                $move = $file->moveTo($target);
                if($move)
                {
                    $uploadedFile[] = ['root'=>$target,'file'=>$file->getName()];
                }
            }
            $upload = (new AliyunService())->upload2Oss($uploadedFile);
            print_R($upload);
            die();
            $oss_urls = array_column($upload->resultArr,'oss');
            print_R($oss_urls);
            die();
        } else {
            echo 'File not uploaded';
        }
        die();
    }

}
