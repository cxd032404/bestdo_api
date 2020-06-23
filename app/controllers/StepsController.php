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


class StepsController extends BaseController
{
	
	/*
     * 创建活动
     * 参数
     * StepsName（必填）：活动名称
	 * club_id（必填）：对应俱乐部
	 * comment：说明文字
	 * member_limit：人数限制
	 * monthly_apply_limit：每月报名次数限制
	 * start_time/end_time：活动时间
	 * apply_start_time/apply_end_time：报名时间
	 * club_member_only：是否只允许俱乐部内成员参加
	 * weekly_rebuild -1表示不重复 0-6周日-周六
     * */
	public function updateStepsAction()
	{
        //验证token
        $return  = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $userInfo = $return['data']['user_info'];
        $currentTime  = time();
        $startDate = date("Y-m-01",$currentTime);
        $date = [];
        for($i = 0;$i<date("t",$currentTime);$i++)
        {
            $date[date("Y-m-d",strtotime($startDate)+$i*86400)] = rand(1000,9000);
        }
	    //接收参数并格式化
		$data = $this->request->get();
        $data['steps'] = json_encode($date);
        $StepsData = trim($data['steps']??"");
        $StepsData = json_decode($StepsData,true);
        //创建活动
        $update = (new StepsService())->updateStepsForUser($userInfo, $StepsData);
        return $this->success($update);


    }
}