<?php
class UploadService extends BaseService
{
	private $msg = 'success';
    private $file_type = ['video'=>['mp4'],'pic'=>['jpg','jpeg','png','bmp']];
    public function getFileTypeList()
    {
        return $this->file_type();
    }
    //从post中上传文件
    //keys：页面元素的key   a.b.c形式
    //exts：扩展名列表
    //max_size：最大文件尺寸
    //min_size：最小文件尺寸
	public function getUploadedFile($keys = ['upload_files'],$exts = [],$max_size=0,$min_size=0)
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
                $pass = 0;
                //过滤元素路径------
                if(count($keys)>0)
                {
                    if(!self::checkKeys($file->getKey(),$keys))
                    {
                        $pass = 1;
                    }
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
                    $type = self::getFileType($file->getExtension());
                    if($type)
                    {
                        $target = ROOT_PATH.'/upload/'.$type.'/'.$file->getName();
                        $upload[$type] = ($upload[$type]??0)+1;
                        $k = $type.'.'.$upload[$type];
                    }
                    $target = ROOT_PATH.'/upload/'.$file->getName();
                    $move = $file->moveTo($target);
                    if($move)
                    {
                        $uploadedFile[$k] = ['root'=>$target,'file'=>$file->getName(),'type'=>$type];
                    }
                }
            }
            $upload = (new AliyunService())->upload2Oss($uploadedFile);
        }
        $return = [];
        foreach($this->file_type as $type => $extList)
        {
            foreach($upload as $name => $root)
            {
                if(substr($name,0,strlen($type))==$type)
                {
                    $return["upload_".$name] = $root;
                }
            }
        }
        return $return;
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
    public function getFileType($ext)
    {
        $typeList = $this->file_type;
        foreach($this->file_type as $type => $extList)
        {
            if(in_array($ext,$extList))
            {
                return $type;
            }
        }
        return false;
    }
}