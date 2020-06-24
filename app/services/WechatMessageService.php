<?php


class WechatMessageService extends BaseService
{
    //对应模板
    private   $sendDataTemplete = [
        'apply_result'=> '俱乐部申请结果',
        'application_note'=> '俱乐部申请通知',
        'toCheckin'=> '活动签到',
        ];



    //俱乐部消息类型
    private  $clubTypeList = [
        'clubJoin',
        'clubLeave',
        'applicationPass',
        'applicationReject',
    ];
    private $activityTypeList = [
        'toCheckin',
        'activityJoin'
    ];

    /*
   *发送微信公众号消息接口
   */
    public function  sendMessage($info,$type)
    {
        $userListSend = $this->generateUserListSend($info,$type);
        if(!$userListSend['result'])
        {
            return $userListSend;
        }
        $contentSend = $this->generateContentSend($info,$type);
        if(!$contentSend['result'])
        {
            return $contentSend;
        }
        return $this->send($userListSend['user_list_info'],$contentSend['content']);
    }

    /*info  包含club_id user_id
     * user_id始终是被操作方user_id
     * 获取接收人信息
     */
    public function generateUserListSend($info,$type)
    {

        if(!isset($info['user_id'])||!$info['user_id'])
        {
            return ['result'=>0,'msg'=>'user_id错误'];
        }
        //俱乐部类
        if(in_array($type,$this->clubTypeList))
        {
            if(!isset($info['club_id'])||!$info['club_id'])
            {
                return ['result'=>0,'msg'=>'club_id错误'];
            }
        }
        //活动类
        else if(in_array($type,$this->activityTypeList))
        {
            if(!isset($info['activity_id'])||!$info['activity_id'])
            {
                return ['result'=>0,'msg'=>'activity_id错误'];
            }
        }else
        {
            return ['result'=>0,'msg'=>'未知类型'];
        }

        $user_list_info= [];
        switch ($type)
        {
            case 'clubJoin' :

            case 'clubLeave':$club_id = $info['club_id'];
                $user_list = (new ClubService())->getClubManagerList($club_id);break;

            case 'applicationPass':

            case 'applicationReject':$user_list[] = $info['user_id'];break;

            case 'activityJoin':$user_list[] = $info['user_id'];break;
            case 'toCheckin':$user_list[] = $info['user_id'];break;

            default:return ['result'=>0,'msg'=>'未知类型'];
        }
        foreach ($user_list as $key =>$value) {
            $user_list_info[$key]['user_id'] = $value;
            $user_info = (new UserService())->getUserInfo($value, 'user_id,openid');
            $user_list_info[$key]['openid'] = isset($user_info->openid)?$user_info->openid:'';
        }
        return ['result'=>1,'msg'=>'','user_list_info'=>$user_list_info];

    }

    /*
     * 获取模板
     */
    public function generateContentSend($info,$type){
        if(!isset($info['user_id'])||!$info['user_id'])
        {
            return ['result'=>0,'msg'=>'operate_id错误'];
        }
        $user_id = $info['user_id'];
        $user_info = (new UserService())->getUserInfo($user_id,'true_name,nick_name');
        if(!$user_info->true_name)
        {
            $user_name = $user_info->nick_name;
        }else
        {
            $user_name = $user_info->true_name;
        }
        //俱乐部类型
        if(in_array($type,$this->clubTypeList))
        {
            if(!isset($info['club_id'])||!$info['club_id'])
            {
                return ['result'=>0,'msg'=>'club_id错误'];
            }
            $club_info = (new ClubService())->getClubInfo($info['club_id'],'club_id,club_name');
            $club_name = $club_info->club_name;
        }
        //活动签到类型
        else if(in_array($type,$this->activityTypeList))
        {
            if(!isset($info['activity_id'])||!$info['activity_id'])
            {
                return ['result'=>0,'msg'=>'activity_id错误'];
            }
            //获取创建者user_id
            $activity_info = (new ActivityService())->getActivityInfo($info['activity_id'],'activity_id,start_time,activity_name,detail');
            $activity_name = $activity_info->activity_name;
            $detail = json_decode($activity_info->detail,true);
            $address = $detail['checkin']['address'];
            $start_time = $activity_info->start_time;
        }else
        {
            return ['result'=>0,'msg'=>'未知类型'];
        }

        $content = '';

        switch ($type){
            //提醒去审核
            case 'clubJoin': $first = '有新的成员申请加入俱乐部';
                             $second = $club_name;
                             $third = $user_name;
                             $four = date('Y-m-d h:i',time());
                             $five = '请前往审批';
                             $templete_id = $this->sendDataTemplete['application_note'] ;break;
            //告知用户审核通过结果
            case 'applicationPass':
                            $first = '您的俱乐部加入申请已有结果';
                            $second = $club_name;
                            $third =  '审核通过';
                            $four  =  date('Y-m-d h:i',time());
                            $five = '快去参加活动吧';
                            $templete_id = $this->sendDataTemplete['apply_result'];break;
            //告知用户审核失败
            case 'applicationReject':
                            $first = '您的俱乐部加入申请已有结果';
                            $second = $club_name;
                            $third =  '申请被拒绝';
                            $four  =  date('Y-m-d h:i',time());
                            $five = '看看其他俱乐部';
                            $templete_id = $this->sendDataTemplete['apply_result'];break;
            //通知管理员成员离开
            case 'clubLeave':
                            $first = '有成员离开俱乐部';
                            $second = $club_name;
                            $third = $user_name;
                            $four = date('Y-m-d h:i',time());
                            $five = '请知晓';
                            $templete_id = $this->sendDataTemplete['application_note'];break;

            case 'activityJoin':
                            $first = '有成员参加活动';
                            $second = $activity_name;
                            $third = $address;
                            $four = date('Y-m-d h:i',time());
                            $five = '活动愉快';break;
            //活动签到提醒
            case 'toCheckin':
                            $first = '有活动待签到';
                            $second = $activity_name;
                            $third = $address;
                            $four = $start_time;
                            $five = '活动愉快';
                            $templete_id = $this->sendDataTemplete['toCheckin'];break;

            default:return ['result'=>0,'msg'=>'未知类型'];
        }
        $content = [
            'first'=>$first,
            'second'=>$second,
            'third'=>$third,
            'four'=>$four,
            'five'=>$five,
            'templete_id'=>$templete_id
        ];
        return ['result'=>1,'msg'=>'','content'=>$content];
    }

    //消息存入redis队列
    public function send($userList,$contentSend)
    {
        $redisKey = $this->config->redisQueue->wechatMessageQueue;

        foreach ($userList as $key=>$value)
        {
            $WechatMessage['touser'] = $value['openid'];
            $WechatMessage['template_id'] = $contentSend['templete_id'];
            $WechatMessage['appid'] = $this->key_config->wechat->appid;
            $WechatMessage['data'] = [
                'data'=>[
                    "first"=>[
                        'value'=>$contentSend['first'],
                        'color'=>'#173177'
                    ],
                    "second"=>[
                        'value'=>$contentSend['second'],
                        'color'=>'#173177'
                    ],
                    "third"=>[
                        'value'=>$contentSend['third'],
                        'color'=>'#173177'
                    ],
                    "four"=>[
                        'value'=>$contentSend['four'],
                        'color'=>'#173177'
                    ],
                    "five"=>[
                        'value'=>$contentSend['five'],
                        'color'=>'#173177'
                    ]
                ]
            ];
            $res = $this->redis->rpush($redisKey,json_encode($WechatMessage));
            if(!$res)
            {
                $error_data[] = $WechatMessage;
            }
        }
        if(!isset($error_data))
        {
            return ['result'=>1,'msg'=>'发送成功'];
        }else
        {
            return ['result'=>0,'msg'=>'发送失败','error_data'=>$error_data];
        }

    }







    public function sendMessage1(){
        $redisKey = $this->config->redisQueue->wechatMessageQueue;

        for($i=0;$i<50;$i++) {

                $message [] = $this->getMessage();

        }
        print_r($message);die();

    }

    //从队列获取信息
    private function getMessage(){
        $redisKey = $this->config->redisQueue->wechatMessageQueue;
        $res = $this->redis->lpop($redisKey);
        return $res;
    }

}