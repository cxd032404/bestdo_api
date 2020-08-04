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
        {   //通知多个管理员
            case 'clubJoin' :

            case 'clubLeave':$club_id = $info['club_id'];
                $user_list = (new ClubService())->getClubManagerList($club_id);break;
            //通知个人
            case 'applicationPass':

            case 'applicationReject':$user_list[] = $info['user_id'];break;

            case 'activityJoin':$user_list[] = $info['user_id'];break;
            case 'toCheckin':$user_list[] = $info['user_id'];break;

            default:return ['result'=>0,'msg'=>'未知类型'];
        }
        foreach ($user_list as $key =>$value) {
            $user_list_info[$key]['user_id'] = $value;
            $user_info = (new UserService())->getUserInfo($value, 'user_id,wechatid');
            $user_list_info[$key]['openid'] = isset($user_info->wechatid)?$user_info->wechatid:'';
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
                            $url = 'http://www.staffhome.cn/club';//俱乐部申请通过跳转链接
                            $first = '恭喜您！您提交的会员申请已通过审核！';
                            $keyword1 = $club_name;
                            $keyword2  =  date('Y年m月d日 h:i',time());
                            $keyword3 = $user_name;
                            $remark = '点击查看详情';
                            $templete_id = $this->config->wechat_template['application_pass'];break;
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
            'keyword1'=>$keyword1,
            'keyword2'=>$keyword2,
            'keyword3'=>$keyword3,
            'remark'=>$remark,
            'templete_id'=>$templete_id,
            'url' => $url
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
            $WechatMessage['url'] = $contentSend['url'];
            $WechatMessage['data'] = [
                    "first"=>[
                        'value'=>$contentSend['first'],
                    ],
                    "keyword1"=>[
                        'value'=>$contentSend['keyword1'],
                    ],
                    "keyword2"=>[
                        'value'=>$contentSend['keyword2'],
                    ],
                    "keyword3"=>[
                        'value'=>$contentSend['keyword3'],
                    ],
                    "remark"=>[
                        'value'=>$contentSend['remark'],
                    ]
            ];
            $res = $this->redis->rpush($redisKey,json_encode($WechatMessage));
            if($res)
            {
                return ['result'=>1,'msg'=>'发送成功'];
            }
            return ['result'=>0,'msg'=>'发送失败','error_data'=>$WechatMessage];
        }


    }



    /*
     * 推送微信公众号信息
     */
    public function sendWechatMessage()
    {
        $redisKey = $this->config->redisQueue->wechatMessageQueue;
        for($i = 0 ;$i<50;$i++)
        {
            $message = $this->redis->lpop($redisKey);
            if($message)
            {
                //有数据发送
                $accessToken = (new WechatService())->getAccessToken($this->key_config->aliyun->wechat->appid,$this->key_config->aliyun->wechat->appsecret);
                $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$accessToken";
                $res = $this->curl->curl_post($sendUrl,$message);
                if($res['errcode']!=0)
                {
                    //未发送成功的记录日志
                    $this->wechatMessage_logger->info($message.json_encode($res));
                }
                $this->wechatMessage_logger->info("发送成功");
            }
        }
    }



    /*
     * 微信公众号消息回复
     */
    public function answer($message = []){
         $event_function_name =strtolower($message->MsgType).'MessageReplay';
         return $this->$event_function_name($message);
    }
    /*
     * 公众号事件回复
     */

    public function eventMessageReplay($message){
        if(strtolower($message->Event)=='subscribe')
        {
            return $this->subscribeMessageReplay($message);
        }else
        {
            return 'success';
        }
    }
    /*
     * 关注事件回复
     */
    public function subscribeMessageReplay($message)
    {
        $toUser= $message->ToUserName??'';
        $fromUser = $message->FromUserName??'';
        $msgType = 'text';
        $createTime = time();
        $content = "感谢关注\"文体之窗\"。\n我们将竭诚为您服务，为您的企业丰富线上生活，提供员工舞台，管理健康大数据。";
            $temp = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
            $temp = sprintf($temp,$fromUser,$toUser,$createTime,$msgType,$content);
            return $temp;
    }

    /*
     * 回复文本消息
     */

    public function textMessageReplay($message){
        return 'success';
        $UserMessage = json_decode(json_encode($message->Content),true)[0];
        //根据用户发送信息回复消息
        $Message_info = (new \HJ\WechatMessage())->find()->toArray();
        $content = '听不懂你说啥';
        foreach ($Message_info as $key=>$apply_message)
        {
            if(strstr($apply_message['message'],$UserMessage))
            {
              $content = $apply_message['replay_message'];
            }
        }
        $toUser= $message->ToUserName??'';
        $fromUser = $message->FromUserName??'';
        $msgType = 'text';
        $createTime = time();
         $temp = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
        $temp = sprintf($temp,$fromUser,$toUser,$createTime,$msgType,$content);
        return $temp;
    }

    /*
     * 图片消息
     */
    public function imageMessageReplay(){
        return 'success';

}


}