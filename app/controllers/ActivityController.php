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


class ActivityController extends BaseController
{
	
	/*
     * 创建活动
     * 参数
     * activityName（必填）：活动名称
	 * club_id（必填）：对应俱乐部
	 * comment：说明文字
	 * member_limit：人数限制
	 * monthly_apply_limit：每月报名次数限制
	 * start_time/end_time：活动时间
	 * apply_start_time/apply_end_time：报名时间
	 * club_member_only：是否只允许俱乐部内成员参加
     * */
	public function activityCreateAction()
	{
        //验证token
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $userInfo = $return['data']['user_info'];
	    //接收参数并格式化
		$data = $this->request->get();
        $activityData['activity_name'] = isset($data['activity_name'])?substr(preg_replace('# #','',$data['activity_name']),0,32):"";
        $activityData['comment'] = isset($data['comment'])?preg_replace('# #','',$data['comment']):"";
        $activityData['start_time'] = isset($data['start_time'])?preg_replace('# #','',$data['start_time']):"";
        $activityData['end_time'] = isset($data['end_time'])?preg_replace('# #','',$data['end_time']):"";
        $activityData['apply_start_time'] = isset($data['apply_start_time'])?preg_replace('# #','',$data['apply_start_time']):"";
        $activityData['apply_end_time'] = isset($data['apply_end_time'])?preg_replace('# #','',$data['apply_end_time']):"";
        $activityData['club_member_only'] = intval($data['club_member_only']??1);
        $activityData['member_limit'] = intval($data['member_limit']??100);
        $activityData['monthly_apply_limit'] = intval($data['monthly_apply_limit']??1);
        $activityData['club_id'] = intval($data['club_id']??0);
        $activityData['weekly_rebuild'] = intval($data['weekly_rebuild']??-1);
        //创建活动
        $create = (new ActivityService())->createActivity($activityData, $userInfo);
		if($create['result'])
        {
            return $this->success($create['data'],$create['msg'],$create['code']);

        }
		else
        {
            return $this->failure([],$create['msg'],$create['code']);

        }
    }
}
