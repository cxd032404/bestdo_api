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
      $params = [
          'user_id'=>$user_id,
          'club_id'=>$club_id,
          'columns'=>'log_id,status',
          'order' => 'log_id desc',
      ];
      //判断是否已是俱乐部成员
      $club_info = (new \hj\ClubMember())->findfirst($params);
      if(isset($club_info->status)&&$club_info->status == 1)
      {
          $return = ['error_code'=>1,'message'=>'已是俱乐部成员'];
          return $return;
      }

      //判断是否提交过申请
      $select_params = [
          'club_id'=>$club_id,
          'user_id'=>$user_id,
          'type'=>1,
          'sub_type'=>1,
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
      $params = [
          'club_id'=>$club_id,
          'user_id'=>$user_id,
          'company_id'=>$company_id,
          'type'=>$type,
          'sub_type'=>$sub_type,
          'operate_user_id'=>$operate_user_id,
          'process_user_id'=>$process_user_id,
          'create_time'=>$create_time,
          'update_time'=>$update_time,
          'process_time'=>$process_time
      ];
      //插入记录
      $insert = (new \hj\ClubMemberLog())->create($params);
      if($insert)
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

        $log_id = $this->request->get('log_id')??0;
        $select_params = [
            'log_id'=>$log_id,
            'result'=>0,   //未处理的申请
        ];
        $club_member_log = (new \hj\ClubMemberLog())->findfirst($select_params);
        if(!isset($club_member_log->log_id))
        {
            $return = ['error_code'=> 1,'message'=>'通过失败'];
            return $return;
        }
        $club_member_log->result  = 1;
        $club_member_log->operate_user_id = $user_id;
        $club_member_log->process_time = time();
        $res = $club_member_log->save();
        if($res)
        {
            $current_time = time();
            $club_id = $club_member_log->club_id;
            $company_id = $company_id;
            $send_user = $club_member_log->user_id;
            $status = 1;
            $create_time = $current_time;
            $update_time = $current_time;
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
        $insert = (new \hj\ClubMemberLog())->create($params);
        return $insert;
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
            $memberShip = json_decode($cache);
            if(isset($permissionCache->member_id ))
            {
                //
            }
            else
            {
                $memberShip = (new \HJ\ClubMember())->findFirst($params);
                if(isset($permission->member_id)) {
                    $this->redis->set($cacheName, json_encode($permission));
                    $this->redis->expire($cacheName, $cacheSettings->expire);
                } else
                {
                    $memberShip = [];
                }
            }
        }
        else
        {
            $memberShip = (new \HJ\ClubMember())->findFirst($params);
            if(isset($permission->member_id)) {
                $this->redis->set($cacheName, json_encode($permission));
                $this->redis->expire($cacheName, $cacheSettings->expire);
            } else
            {
                $memberShip = [];
            }
        }
        return $memberShip;
    }
    
    public function getUserClubPermission($user_id = 0,$club_id = 0,$cache = 1)
    {
        $cacheSettings = $this->config->cache_settings->user_club_permission;
        $cacheName = $cacheSettings->name.$user_id."_".$club_id;
        $userClubMemberShip = $this->getUserClubMembership($user_id,$club_id,$cache);
        $permission = $userClubMemberShip->permission??0;
        return $permission;
    }
}