<?php

use OSS\Core\OssException;
use OSS\OssClient;
class AliyunService
{
    private static $_config = [
        'END_POINT'=>'oss-cn-shanghai.aliyuncs.com',
        'BUCKET'=>'xrace-pic',
        'ACCESS_KEY_ID'=>'LTAI4FkbExDy9cEfwqNfb93X',
        'ACCESS_KEY_SECRET'=>'57iMpXwB0UYR71tXGuacAHmoCDtTaL'
    ];
    /*
    *
    */
    public static function upload2Oss($fileArr = [])
    {
        $bucket = self::$_config['BUCKET'];
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
    public static function getOssClient()
    {
        try {
            $ossClient = new OssClient(
                self::$_config['ACCESS_KEY_ID'],
                self::$_config['ACCESS_KEY_SECRET'],
                self::$_config['END_POINT'], false);
        } catch (OssException $e) {
            //Log::Info(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            //Log::Info($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public static function getBucketName()
    {
        return getenv('OSS_BUCKET');
    }

    /**
     * 工具方法，创建一个bucket
     */
    public static function createBucket()
    {
        $ossClient = self::getOssClient();
        if (is_null($ossClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try {
            $ossClient->createBucket($bucket, $acl);
        } catch (OssException $e) {
            //Log::Info(__FUNCTION__ . ": FAILED\n");
            //Log::Info($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    /**
     * Wait for bucket meta sync
     */
    public static function waitMetaSync()
    {
        if (getenv('TRAVIS')) {
            sleep(10);
        }
    }
}
