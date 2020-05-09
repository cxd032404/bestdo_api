<?php

use OSS\Core\OssException;
use OSS\OssClient;
class AliyunService extends BaseService
{
    /*
    *
    */
    public function upload2Oss($fileArr = [])
    {
        $bucket = $this->key_config->aliyun->oss->BUCKET;
        $client = self::getOssClient();
        $returnArr = [];
        foreach($fileArr as $key => $file)
        {
            if(!isset($file['error']) || $file['error'] == 0)
            {
                $local_file = $file['root'];
                $object = "public/xrace/images".$file['file'];
                try {
                    $res = $client->uploadFile($bucket, $object, $local_file);
                    $returnArr[$key] = $res['info']['url']??"";
                }catch(\OSS\Core\OssException $e) {
                    $returnArr[$key] = false;
                }
            }
        }
        return $returnArr;
    }
    public function getOssClient()
    {
        try {
            $ossClient = new OssClient(
                $this->key_config->aliyun->oss->ACCESS_KEY_ID,
                $this->key_config->aliyun->oss->ACCESS_KEY_SECRET,
                $this->key_config->aliyun->oss->END_POINT, false);
        } catch (OssException $e) {
            //Log::Info(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            //Log::Info($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }
}
