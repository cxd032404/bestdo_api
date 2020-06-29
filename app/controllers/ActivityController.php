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
	 * weekly_rebuild -1表示不重复 0-6周日-周六
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
        $activityData['activity_name'] = isset($data['activity_name'])?substr(trim($data['activity_name'],'#'),0,32):"";
        $activityData['comment'] = isset($data['comment'])?trim($data['comment'],'#'):"";
        $activityData['start_time'] = isset($data['start_time'])?trim($data['start_time'],'#'):"";
        $activityData['end_time'] = isset($data['end_time'])?trim($data['end_time'],'#'):"";
        $activityData['apply_start_time'] = isset($data['apply_start_time'])?trim($data['apply_start_time'],'#'):"";
        $activityData['apply_end_time'] = isset($data['apply_end_time'])?trim($data['apply_end_time'],'#'):"";
        $activityData['club_member_only'] = intval($data['club_member_only']??1);
        $activityData['member_limit'] = intval($data['member_limit']??100);
        $activityData['monthly_apply_limit'] = intval($data['monthly_apply_limit']??1);
        $activityData['club_id'] = intval($data['club_id']??0);
        $activityData['weekly_rebuild'] = intval($data['weekly_rebuild']??-1);
        $activityData['connect_activity_id'] = intval($data['connect_activity_id']??0);
        $activityData['checkin'] = json_decode($data['checkin']??"",true);

        //ALTER TABLE `config_activity` ADD `connect_activity_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联的活动id' AFTER `activity_id`, ADD INDEX (`connect_activity_id`);

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
    /*
     * 更新活动
     * 参数
     * activityId（必填）：活动ID
     * activityName（必填）：活动名称
	 * club_id：对应俱乐部
	 * comment：说明文字
	 * member_limit：人数限制
	 * monthly_apply_limit：每月报名次数限制
	 * start_time/end_time：活动时间
	 * apply_start_time/apply_end_time：报名时间
	 * club_member_only：是否只允许俱乐部内成员参加
     * */
    public function activityUpdateAction()
    {
        //验证token
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $userInfo = $return['data']['user_info'];
        //接收参数并格式化
        $data = $this->request->get();
        $activityId = intval($data['activity_id']??0);
        $activityData['activity_name'] = isset($data['activity_name'])?substr(trim($data['activity_name'],'#'),0,32):"";
        $activityData['comment'] = isset($data['comment'])?trim($data['comment'],'#'):"";
        $activityData['start_time'] = isset($data['start_time'])?trim($data['start_time'],'#'):"";
        $activityData['end_time'] = isset($data['end_time'])?trim($data['end_time'],'#'):"";
        $activityData['apply_start_time'] = isset($data['apply_start_time'])?trim($data['apply_start_time'],'#'):"";
        $activityData['apply_end_time'] = isset($data['apply_end_time'])?trim($data['apply_end_time'],'#'):"";
        $activityData['club_member_only'] = intval($data['club_member_only']??1);
        $activityData['member_limit'] = intval($data['member_limit']??100);
        $activityData['monthly_apply_limit'] = intval($data['monthly_apply_limit']??1);
        $activityData['club_id'] = intval($data['club_id']??0);
        $activityData['weekly_rebuild'] = intval($data['weekly_rebuild']??-1);
        $activityData['connect_activity_id'] = intval($data['connect_activity_id']??0);
        $activityData['checkin'] = json_decode($data['checkin']??"",true);

        //更新活动
        $update = (new ActivityService())->updateActivity($activityId,$activityData, $userInfo);
        if($update['result'])
        {
            return $this->success($update['data'],$update['msg'],$update['code']);

        }
        else
        {
            return $this->failure([],$update['msg'],$update['code']);

        }
    }
    /*
 * 报名活动
 * 参数
 * mobile（必填）：手机号
 * user_name（必填）：用户姓名
 * department（必填）：所属部门
 * activity_id（必填）：活动id
 * UserToken（必填）：用户token
 * */
    public function activityApplyAction()
    {
        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
        //接收参数并格式化
        $data = $this->request->get();
        $activity_id = isset($data['activity_id'])?intval($data['activity_id']):0;
        //调用手机号密码登录方法
        $return  = (new ActivityService())->activityApply($activity_id,$user_id);
        //返回值判断
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        return $this->success($return['data'],$return['msg'],$return['code']);
    }
    public function checkPositionForCheckinAction()
    {
        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $data = $this->request->get();
        //接收参数并格式化
        $activity_id = intval($data['activity_id']??0);
        $position = json_decode($data['position']??'{"longitude":121.54619,"latitude":31.32054,"address":"\u4e0a\u6d77\u5e02\u6768\u6d66\u533a\u5ae9\u6c5f\u8def861\u53f7"}',true);
        $return = (new ActivityService())->checkPostitionForCheckin($activity_id,$return['data']['user_info']->user_id,$position);
        if($return['result'])
        {
            $this->success($return['data']??[],$return['msg']);
        }
        else
        {
            $this->failure([],$return['msg']);
        }
    }
    public function activityCheckinAction()
    {
        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $data = $this->request->get();
        //接收参数并格式化
        $activity_id = intval($data['activity_id']??0);
        $position = json_decode($data['position']??'{"longitude":121.54619,"latitude":31.32054,"address":"\u4e0a\u6d77\u5e02\u6768\u6d66\u533a\u5ae9\u6c5f\u8def861\u53f7"}',true);
        $return = (new ActivityService())->activityCheckin($activity_id,$return['data']['user_info']->user_id,$position);
        if($return['result'])
        {
            $this->success($return['data']??[],$return['msg']);
        }
        else
        {
            $this->failure([],$return['msg']);
        }
    }


    /*
     * 取消活动
     */

    public function activityCancelAction(){
        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
        //接收参数并格式化
        $data = $this->request->get();
        $activity_id = isset($data['activity_id'])?intval($data['activity_id']):0;
        $return = (new ActivityService())->activityCancel($user_id,$activity_id);
        if($return['result'])
        {
            $this->success($return['data']??[],$return['msg']);
        }
        else
        {
            $this->failure([],$return['msg']);
        }

    }
    public function refreshAction()
    {
        $data = $this->request->get();
        $activity_id = isset($data['activity_id'])?intval($data['activity_id']):0;
        $activity_info = (new ActivityService())->getActivityInfo($activity_id,"*",0);
        $this->success($activity_info);
    }

    /*
     * 获取用户当月活动列表
     */
    public function UserMonthlyActivitiesAction(){
        /*验证token开始*/
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        /*验证token结束*/
        $user_id = isset($return['data']['user_info']->user_id)?$return['data']['user_info']->user_id:0;
        //接收参数并格式化
        $data = $this->request->get();

        $month = $data['month']?($data['month']):date('m',time());
        if(strlen($month)==1)
        {
            $month = '0'.$month;
        }
        $return = (new ActivityService())->getMonthlyActivityList($user_id,$month);
        if($return['result']) {
            $this->success($return['data'] ?? [], $return['msg']);
        }else
        {
            $this->failure([],$return['msg']);
        }
    }

}
