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
        $type = isset($data['type'])?($data['type']):"user";
        if($type=="config")
        {
            $id = trim($data['id'])?trim($data['id']):"";
        }
        else
        {
            $id = intval($data['id'])?intval($data['id']):0;
        }

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
                (new ActivityService())->getActivityMemberCount($id,0);
                if($activityInfo->club_id>0)
                {
                    (new ActivityService())->getActivityListByCompany($activityInfo->company_id,'*',$activityInfo->club_id,0);
                }
                (new ActivityService())->getActivityListByCreater($activityInfo->company_id,$activityInfo->create_user_id,'*',$activityInfo->club_id,0);
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
                $company_id = $clubInfo->company_id;
                (new ClubService())->getClubListByCompany($company_id,"*",0);
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
        }
        elseif($type=="post")
        {
            $postsInfo = (new PostsService())->getPosts($id,"*",0);
            if(isset($postsInfo->post_id))
            {
                $this->success($postsInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="company")
        {
            $companyInfo = (new CompanyService())->getCompanyInfo($id,"*",0);
            if(isset($companyInfo->company_id))
            {
                $this->success($companyInfo,"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="page")
        {
            $pageInfo = (new PageService())->getPageInfoById($id,"*",0);
            if(isset($pageInfo->page_sign))
            {
                $pageInfo = (new PageService())->getPageInfoBySign($pageInfo->company_id,$pageInfo->page_sign,"*",0);
                $pageElementList = (new PageService())->getPageElementByPage($pageInfo->page_id,"*",[],$order = "element_type DESC",0);
                $this->success([$pageInfo,$pageElementList],"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
        elseif($type=="config")
        {
            $config = (new ConfigService())->getConfig($id,0);
            if(isset($config->config_sign))
            {
                $this->success([$config],"ok",200);
            }
            else
            {
                $this->failure([],"not found","400");
            }
        }
    }

}
