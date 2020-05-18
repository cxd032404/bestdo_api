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
                        $k = 'upload_'.$type.'.'.$upload[$type];
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
        return $this->sortUpload($upload);
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
    public function sortUpload($fileArr)
    {
        $fileList = [];$return = [];
        foreach($this->file_type as $type => $extList)
        {
            foreach($fileArr as $name => $path)
            {
                $t = explode(".",$name);
                if($t['0'] == "upload_".$type)
                {
                    $fileList[$t['0']][count($fileList[$t['0']]??[])+1] = $path;
                }
            }
            foreach($fileList["upload_".$type]??[] as $k => $path)
            {
                $return["upload_".$type.".".$k] = $path;
            }
        }
        return $return;
    }
    public function parthSource($sourceList)
    {
        $return = [];
        foreach($sourceList as $name => $path)
        {
            foreach($this->file_type as $type => $extList)
            {
                $t = explode(".",$path);
                $ext = $t[count($t)-1];
                if(in_array($ext,$extList))
                {
                    $fileArr = ['path'=>$path,'name'=>$name,'type'=>$type,'suffix'=>($type=="video")?"?x-oss-process=video/snapshot,t_1000,f_jpg,w_300,h_300,m_fast":""];
                    $return[] = $fileArr;
                }
            }
        }
        return $return;
    }
}