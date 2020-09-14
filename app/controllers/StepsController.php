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
        //接收参数并格式化
        $data = $this->request->get();
        $code = trim($data['code']??"");
        $iv = trim($data['iv']??"");
        $data = trim($data['encryptedData']??"");
        $app_id = $this->request->getHeader("Appid")??201;
        //解码
        $stepsData = (new WechatService)->decryptData($data,$iv,$this->key_config->tencent,$code,$app_id);
        //更新步数
        $update = (new StepsService())->updateStepsForUser($userInfo, $stepsData);
        return $this->success($update);
    }
}
