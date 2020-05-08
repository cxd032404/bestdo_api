<?php
class UploadService extends BaseService
{
	private $msg = 'success';

    //从post中上传文件
    //keys：页面元素的key   a.b.c形式
    //exts：扩展名列表
    //max_size：最大文件尺寸
    //min_size：最小文件尺寸
	public function getUploadedFile($keys = ['upload_img'],$exts = [],$max_size=0,$min_size=0)
    {
        $upload = [];
        foreach($keys as $k => $v)
        {
            $keys[$k] = explode(".",$v);
        }
        if ($this->request->hasFiles(true) == true) {
            $uploadedFile = [];
            foreach ($this->request->getUploadedFiles() as $file)
            {
                echo "size:".$file->getSize()."<br>";
                $pass = 0;

                //过滤元素路径------
                if(!self::checkKeys($file->getKey(),$keys))
                {
                    $pass = 1;
                }
                //过滤元素尺寸------
                if(!self::checkSize($file->getSize(),$max_size,$min_size))
                {
                    $pass = 1;
                }
                //过滤文件扩展名------
                if(!self::checkExt($file->getExtension(),$exts))
                {
                    $pass = 1;
                }
                if($pass==0)
                {
                    $target = ROOT_PATH.'/upload/'.$file->getName();
                    $move = $file->moveTo($target);
                    if($move)
                    {
                        $uploadedFile[$file->getKey()] = ['root'=>$target,'file'=>$file->getName()];
                    }
                }
            }
            $upload = (new AliyunService())->upload2Oss($uploadedFile);
        }
        return $upload;
    }
    private function checkKeys($key,$keys)
    {
        //过滤元素路径------
        foreach($keys as $k)
        {
            $t = implode(".",$k);
            if(((substr($key,0,strlen($t)+1)) == $t.".") || $key==$t)
            {
                //echo substr($key,0,strlen($t));
                return true;
            }
        }
        return false;
    }
    private function checkSize($size,$max_size = 0,$min_size = 0)
    {
        if($max_size>0 && $size>$max_size)
        {
            return false;
        }
        if($min_size>0 && $size<$min_size)
        {
            return false;
        }
        return true;
    }
    private function checkExt($ext,$exts = [])
    {
        if(count($exts)>0 && !in_array($ext,$exts))
        {
            return false;
        }
        return true;
    }
}