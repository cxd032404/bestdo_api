<?php
/*
 * 2020/6/10
 * author shishuozheng
 */

class ClubService extends BaseService
{
    /*
     * 加入俱乐部
     */
  public function joinClub($user_id,$company_id){
      $return = ['error_code'=> 0,'message'=>'申请成功'];
      $club_id = $this->request->get('club_id')??0;
      //判断是否已是俱乐部成员
      $member_ship = $this->getUserClubMembership($user_id,$club_id,0);
      if(isset($member_ship->member_id))
      {
          $return = ['error_code'=> 1,'message'=>'已经是俱乐部成员'];
          return $return;
      }
      //判断是否提交过申请
      $conditons = 'club_id = :club_id: and user_id = :user_id: and type = :type: and sub_type = :sub_type:';
      $select_params = [
          $conditons,
          'bind'=>[
          'club_id'=>$club_id,
          'user_id'=>$user_id,
          'type'=>1,
          'sub_type'=>1,
              ],
          'columns'=>'log_id,result',
          'order' => 'log_id desc',
      ];
      $club_member_log = (new \hj\ClubMemberLog())->findfirst($select_params);
      if(isset($club_member_log->log_id)&&$club_member_log->result == 0)
      {
          $return = ['error_code'=> 0,'message'=>'申请成功'];
          return $return;
      }
      $current_time = time();
      $type = 1;
      $sub_type = 1;
      $operate_user_id = $user_id;
      $process_user_id = 0;
      $create_time = date("Y-m-d H:i:s",$current_time);
      $update_time = date("Y-m-d H:i:s",$current_time);
      $process_time = date("Y-m-d H:i:s",$current_time);

      $insert = new \hj\ClubMemberLog();
      $insert->club_id = $club_id;
      $insert->user_id = $user_id;
      $insert->company_id = $company_id;
      $insert->type = $type;
      $insert->sub_type = $sub_type;
      $insert->operate_user_id = $operate_user_id;
      $insert->create_time = $create_time;
      $insert->update_time = $update_time;
      $insert->process_time = $process_time;
      $insert_result = $insert->create();
      if($insert_result)
      {
          $return = ['error_code'=> 0,'message'=>'申请成功'];
      }else
      {
          $return = ['error_code'=> 1,'message'=>'申请失败'];
      }
      //此处留有微信公众号信息推送
      return  $return;

  }

  /*
   * 撤销俱乐部申请
   */
    public function cancelApplication($user_id){
        $return = ['error_code'=> 0,'message'=>'取消成功'];
        $log_id = $this->request->get('log_id')??0;
        $club_id = $this->getClubId($log_id);


        $select_params = [
            'log_id'=>$log_id,
            'user_id'=>$user_id,
            'type'=>1,
            'sub_type'=>1,
            'result'=>0,   //未处理的申请
        ];
        $club_member_log = (new \hj\ClubMemberLog())->findfirst($select_params);
        if(!isset($club_member_log->log_id))
        {
            $return = ['error_code'=> 1,'message'=>'取消失败'];
            return $return;
        }
        $club_member_log->result = 2; //取消
        $res = $club_member_log->update();
        if($res)
        {
            $return = ['error_code'=> 0,'message'=>'取消成功'];
        }else
        {
            $return = ['error_code'=> 1,'message'=>'取消失败'];
        }
        return $return;
    }

    /*
     * 管理员操作用户申请
     */
    public function passApplication($user_id,$company_id)
    {
        //预留判断管理员操作
        $log_id = $this->request->getPost('log_id')??0;
        $club_id = $this->getClubId($log_id)??0;
        $permission = $this->getUserClubPermission($user_id,$club_id);

        if($permission == 0) {
                $return = ['error_code' => 1, 'message' => '没有权限'];
        }

        $conditons = "log_id = :log_id: and result = :result:";
        $select_params = [
            $conditons,
            'bind'=>[
                'log_id'=>$log_id,
                'result'=>0
            ],
        ];
        $club_member_log = (new \hj\ClubMemberLog())->findFirst($select_params);

        if(!isset($club_member_log->log_id))
        {
                $return = ['error_code'=> 1,'message'=>'通过成功'];
                return $return;
        }
        $current_time = time();
        $club_member_log->result  = 1;
        $club_member_log->operate_user_id = $user_id;
        $club_member_log->process_time = date("Y-m-d H:i:s",$current_time);
        $res = $club_member_log->save();
        if($res)
        {
            $club_id = $club_member_log->club_id;
            $company_id = $company_id;
            $send_user = $club_member_log->user_id;
            $status = 1;
            $create_time = date("Y-m-d H:i:s",$current_time);
            $update_time = date("Y-m-d H:i:s",$current_time);
            $detail = '';
            $insert_params = [
                'club_id'=>$club_id,
                'company_id'=>$company_id,
                'user_id'=>$send_user, // 发起申请的人
                'status'=>$status,
                'create_time'=>$create_time,
                'update_time'=>$update_time,
                'detail'=>$detail
            ];
            $insert = $this->updateClubMember($insert_params);
            if($insert)
            {
                $this->getUserClubMembership($send_user,$club_id,0);
                $return = ['error_code'=> 0,'message'=>'通过成功'];

            }else
            {
                $return = ['error_code'=> 1,'message'=>'通过失败'];
            }
        }else
        {
            $return = ['error_code'=> 1,'message'=>'通过失败'];
        }

        return $return;

    }



    /*
     * 添加用户到俱乐部
     */
    public function updateClubMember($params){
        $insert = (new \hj\ClubMember())->create($params);
        return $insert;
    }

    public function getClubId($log_id){
        $params = [
            'log_id'=>$log_id,
            'columns'=>'club_id'
        ];
        $club_member_log = (new \hj\ClubMemberLog())->findFirst($params);
        return $club_member_log;
    }

    /*
     * 检测用户是否是管理员
     */
    public function getUserClubMembership($user_id = 0,$club_id = 0,$cache = 1)
    {
        $cacheSettings = $this->config->cache_settings->user_club_membership;
        $cacheName = $cacheSettings->name.$user_id."_".$club_id;
        $params =             [
            "club_id = '".$club_id."' and user_id = '".$user_id."'",
            "columns" => '*',
            "order" => 'member_id desc'
        ];
        if($cache == 1)
        {
            $cache = $this->redis->get($cacheName);
            if($cache)
            {
                $memberShip = json_decode($cache);
                if(is_array($memberShip))
                {
                }
                else
                {
                    $memberShip = (new \HJ\ClubMember())->findFirst($params);
                    //没拿到
                    if(!isset($memberShip->member_id))
                    {
                        $memberShip = [];
                    }
                    //已经离开俱乐部
                    elseif($memberShip->status==0)
                    {
                        $memberShip = [];
                    }
                    $this->redis->set($cacheName, json_encode($memberShip));
                    $this->redis->expire($cacheName, $cacheSettings->expire);
                }
            }
            else
            {
                $memberShip = (new \HJ\ClubMember())->findFirst($params);
                //没拿到
                if(!isset($memberShip->member_id))
                {
                    $memberShip = [];
                }
                //已经离开俱乐部
                elseif($memberShip->status==0)
                {
                    $memberShip = [];
                }
                $this->redis->set($cacheName, json_encode($memberShip));
                $this->redis->expire($cacheName, $cacheSettings->expire);
            }
        }
        else
        {
            $memberShip = (new \HJ\ClubMember())->findFirst($params);
            //没拿到
            if(!isset($memberShip->member_id))
            {
                $memberShip = [];
            }
            //已经离开俱乐部
            elseif($memberShip->status==0)
            {
                $memberShip = [];
            }
            $this->redis->set($cacheName, json_encode($memberShip));
            $this->redis->expire($cacheName, $cacheSettings->expire);
        }
        return $memberShip;
    }

    public function getUserClubPermission($user_id = 0,$club_id = 0,$cache = 1)
    {
        $cacheSettings = $this->config->cache_settings->user_club_permission;
        $cacheName = $cacheSettings->name.$user_id."_".$club_id;
        if($cache == 1)
        {
            $permission = $this->redis->get($cacheName);
            if(!empty($permission))
            {
                $userClubMemberShip = $this->getUserClubMembership($user_id,$club_id,1);
                $permission = $userClubMemberShip->permission??0;
                $this->redis->set($cacheName, $permission);
                $this->redis->expire($cacheName, $cacheSettings->expire);
            }
            else
            {
                //
            }
        }
        else
        {
            $userClubMemberShip = $this->getUserClubMembership($user_id,$club_id,1);
            $permission = $userClubMemberShip->permission??0;
            $this->redis->set($cacheName, $permission);
            $this->redis->expire($cacheName, $cacheSettings->expire);
        }
        return $permission;
    }
}