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

	public function postAction( )
	{
        echo "here";
	    if ($this->request->hasFiles() == true) {
            foreach ($this->request->getUploadedFiles() as $file){
                print_R($file);
                echo $file->getName(), ' ', $file->getSize(), '\n';
                $target = ROOT_PATH.'/upload/'.$file->getName();
                echo "target:".$target."\n";
                $file->moveTo($target);
            }
        } else {
            echo 'File not uploaded';
        }
        die();
    }

}
