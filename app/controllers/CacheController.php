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
use Phalcon\Mvc\Controller;


class CacheController extends BaseController
{
    public function refreshAction()
    {
        $data = $this->request->get();
        $id = isset($data['id'])?intval($data['id']):0;
        $type = isset($data['type'])?($data['type']):"user";
        if($type=="user")
        {
            $userInfo = (new UserService())->getUserInfo($id,"*",0);
            if(isset($userInfo->user_id))
            {
                $this->success($userInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        if($type=="list")
        {
            $listInfo = (new ListService())->getListInfo($id,"*",0);
            if(isset($listInfo->list_id))
            {
                $this->success($listInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="activity")
        {
            $activityInfo = (new ActivityService())->getActivityInfo($id,"*",0);
            if(isset($activityInfo->activity_id))
            {
                $this->success($activityInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="club")
        {
            $clubInfo = (new ClubService())->getClubInfo($id,"*",0);
            if(isset($clubInfo->club_id))
            {
                $this->success($clubInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="list")
        {
            $listInfo = (new ListService())->getListInfo($id,"*",0);
            if(isset($listInfo->list_id))
            {
                $this->success($listInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }    }

}
