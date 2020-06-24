<?php


class WechatMessageService extends BaseService
{
    //对应模板
    private   $sendDataTemplete = [
        'apply_result'=> '俱乐部申请结果',
        'application_pass'=> '俱乐部申请结果',
        'checkin'=> '俱乐部申请结果',
        ];



    //俱乐部消息类型
    private  $clubTypeList = [
        'clubJoin',
        'club_leave',
        'applicationPass',
        'applicationReject'
    ];
    private $activityTypeList = [
        'checkin',
        'ActivityJoin',
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
        else if(in_array($this->activityTypeList))
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
            case 'reject':$user_list[] = $info['user_id'];break;
            case 'applicationReject':$create_user_id = (new \HJ\Activity())->findFirst(['activity_id ='.$info['activity_id'],'columns'=>'create_user_id']);
                $user_list[] = $create_user_id->create_user_id;break;
            default:return ['result'=>0,'msg'=>'未知类型'];
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
            $activity_info = (new ActivityService())->getActivityInfo($info['activity_id'],'activity_id,activity_name');
            $activity_name = $activity_info->activity_name;
        }else
        {
            return ['result'=>0,'msg'=>'未知类型'];
        }

        $content = '';

        switch ($type){
            case 'join':$content = $user_name.$this->templete['join'].$club_name;break;//史说政申请加入足球俱乐部
            case 'pass': $content = $club_name.$this->templete['pass']; break; //足球俱乐部通过了你的申请
            case 'reject':$content = $club_name.$this->templete['reject'];break;//足球俱乐部拒绝了你的申请
            case 'leave':$content = $user_name.$this->templete['leave'].$club_name;break;
            case 'activity':$content = $user_name.$this->templete['activity'].$activity_name;
                defalt:  return ['result'=>0,'msg'=>'类型有误'];
        }
        return ['result'=>1,'msg'=>'','content'=>$content];
    }

    //消息存入redis队列
    public function send($userList,$contentSend)
    {
        $redisKey = $this->config->redisQueue->wechatMessageQueue;
        foreach ($userList as $key=>$value)
        {
            $message = [];
            $message['user_id'] = $value['user_id'];
            $message['openid'] = $value['openid'];
            $message['content'] = $contentSend;
            print_r($message);die();
            $res = $this->redis->rpush($redisKey,json_encode($message));
            if(!$res)
            {
                $error_data[] = $message;
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







    public function sendMe11ssage(){
        $redisKey = $this->config->redisQueue->wechatMessageQueue;

        for($i=50;$i<50;$i++) {
            $message_info = $send_message_info = $this->getMessage();
            if(!$message_info)
            {
                break;
            }
            $accessToken = (new WechatService())->checkWechatAccessToken();
            $res = (new WechatService())->sendWechatMessage($accessToken, $message_info['openid'], '-Qq05dZSlDIf7LyuSWf0V3tJ9AuXjypdempKDTSGUio', $message_info['content']);
            $this->logger->info(json_encode($res));
            //发送失败 塞回队列
            if (!isset($res['errcode']) || $res['errcode']) {
                $this->redis->rpush($redisKey, json_encode($message_info));
            }
        }

    }

    //从队列获取信息
    private function getMessage(){
        $redisKey = $this->config->redisQueue->wechatMessageQueue;
        $res = $this->redis->lpop($redisKey);
        return json_decode($res);
    }

}