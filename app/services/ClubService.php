<?php
/*
 * 2020/6/10
 * author shishuozheng
 */

class ClubService extends BaseService
{
    private $permission = [
        0=>'普通会员',
        1=>'管理员',
        9=>'俱乐部创建人',
        99=>'超级管理员',
    ];



    /*
     * 加入俱乐部
     */
  public function joinClub($user_id,$club_id){
      if(!$club_id)
      {
          $return = ['result'=> 0,'msg'=>'club_id未传'];
          return $return;
      }
      //$return = ['result'=> 0,'msg'=>'申请成功'];
      //判断当前俱乐部是否允许加入
      $club_info = $this->getClubInfo($club_id,'allow_enter');
      if($club_info->allow_enter == 0)
      {
          $return = ['result'=> 0,'msg'=>'俱乐部不允许加入'];
          return $return;
      }
      //判断是否已是俱乐部成员

      $member_ship = $this->getUserClubMembership($user_id,$club_id,0);
      if(isset($member_ship->member_id)&&$member_ship->status==1)
      {
          $return = ['result'=> 1,'msg'=>'已经是俱乐部成员了，无需重复申请'];
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
      $club_member_log = (new \HJ\ClubMemberLog())->findfirst($select_params);
      if(isset($club_member_log->log_id)&&$club_member_log->result == 0)
      {
          $return = ['result'=> 0,'data'=>$club_member_log,'msg'=>'已经提交过无须重复申请'];
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
      $user_info = (new UserService())->getUserInfo($user_id,"user_id,company_id");
      $insert = new \HJ\ClubMemberLog();
      $insert->club_id = $club_id;
      $insert->user_id = $user_id;
      $insert->company_id = $user_info->company_id;
      $insert->type = $type;
      $insert->sub_type = $sub_type;
      $insert->operate_user_id = $operate_user_id;
      $insert->process_user_id = $process_user_id;
      $insert->create_time = $create_time;
      $insert->update_time = $update_time;
      $insert->process_time = $process_time;
      $insert_result = $insert->create();
      if($insert_result)
      {
          $return = ['result'=> 1,'data'=>$insert,'msg'=>'申请成功'];
      }else
      {
          $return = ['result'=> 0,'msg'=>'申请失败'];
      }
      //此处留有微信公众号信息推送
      return  $return;

  }

  /*
   * 撤销俱乐部申请
   */
    public function applicationCancel($user_id,$log_id = 0){
        $conditons = 'log_id = :log_id: and user_id = :user_id: and type = :type: and sub_type = :sub_type: and result=:result:';
        $select_params = [
            $conditons,
            'bind'=>[
            'log_id'=>$log_id,
            'user_id'=>$user_id,
            'type'=>1,
            'sub_type'=>1,
            'result'=>0,   //未处理的申请
            ],
        ];
        $club_member_log = (new \HJ\ClubMemberLog())->findfirst($select_params);
        if(!isset($club_member_log->log_id))
        {
            $return = ['result'=> 1,'msg'=>'取消失败'];
            return $return;
        }
        $club_member_log->result = 2; //取消
        $res = $club_member_log->update();
        if($res)
        {
            $return = ['result'=> 1,'msg'=>'取消成功'];
        }else
        {
            $return = ['result'=> 0,'msg'=>'取消失败'];
        }
        return $return;
    }

    /*
     * 管理员操作用户申请
     */
    public function applicationOperate($user_id,$operation,$log_ids = 0)
    {
        if($operation == 'pass')
        {
            $success = 0;
            foreach ($log_ids as $log_id)
            {
                $res = $this->ApplicationPass($user_id,$log_id);
                if($res)
                {
                    $success ++;
                }
            }
            if($success)
            {
                $return = ['result'=> 1,'msg'=>'操作成功'];
            }else{
                $return = ['result'=> 0,'msg'=>'操作失败'];
            }
            return $return;
        }elseif($operation == 'reject')
        {

            $success = 0;
            foreach ($log_ids as $log_id)
            {
                $res = $this->applicationReject($user_id,$log_id);
                if($res)
                {
                    $success ++;
                }
            }
            if($success)
            {
                $return = ['result'=> 1,'msg'=>'操作成功'];
            }else{
                $return = ['result'=> 0,'msg'=>'操作失败'];
            }
            return $return;

        }
    }

    /*
     * 管理员拒绝用户申请
     */
    public function applicationReject($user_id,$log_id = 0){
        $club_member_log_info = $this->getClubMemberLog($log_id);
        $club_id = isset($club_member_log_info->club_id)??0;
        $permission = $this->getUserClubPermission($user_id,$club_id,0);
        if($permission == 0)
        {
            return false;
        }
        $conditons = "log_id = :log_id: and result = :result:";
        $select_params = [
            $conditons,
            'bind'=>[
                'log_id'=>$log_id,
                'result'=>0
            ],
        ];
        $club_member_log = (new \HJ\ClubMemberLog())->findFirst($select_params);
        if(!isset($club_member_log->log_id))
        {
            return true;  //修改过跳回无处理
        }
        $current_time = time();
        $club_member_log->result  = 2;
        $club_member_log->process_user_id = $user_id;
        $club_member_log->process_time = date("Y-m-d H:i:s",$current_time);
        $res = $club_member_log->save();
        if($res)
        {
            return true;
        }else
        {
            return false;
        }
    }



 /*
  *通过申请记录添加成员
  * log_id 记录id user_id 处理人id
  */
   public function applicationPass($user_id,$log_id = 0){
       $club_member_log_info = $this->getClubMemberLog($log_id);
       $club_id = $club_member_log_info->club_id;
       $permission = $this->getUserClubPermission($user_id,$club_id,0);
       if(!$permission)
       {
           return false;
       }
       //判断人数是否超限
       $club_info = $this->getClubInfo($club_id,'member_limit');
       $member_limit = $club_info->member_limit;
       $club_member_count = $this->getClubMemberCount($club_id);
       if($member_limit<=$club_member_count)
       {
           return false;
       }
       $conditons = "log_id = :log_id: and result = :result:";
       $select_params = [
           $conditons,
           'bind'=>[
               'log_id'=>$log_id,
               'result'=>0
           ],
       ];
       $club_member_log = (new \HJ\ClubMemberLog())->findFirst($select_params);
       if(!isset($club_member_log->log_id))
       {
           return true;  //修改过跳回无处理
       }
       $current_time = time();
       $club_member_log->result  = 1;
       $club_member_log->process_user_id = $user_id;
       $club_member_log->process_time = date("Y-m-d H:i:s",$current_time);
       $res = $club_member_log->save();
       if($res)
       {
           $user_info = (new UserService())->getUserInfo($user_id,"user_id,company_id");
           $club_id = $club_member_log->club_id;
           $company_id = $user_info->company_id;
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
               'detail'=>json_encode($detail)
           ];
           $insert = $this->addClubMember($insert_params);
           if($insert)
           {
               //刷新缓存
               $this->getUserClubMembership($send_user,$club_id,0);
               $this->getUserClubList($send_user,'member_id,club_id',1,0);
               $this->getUserClubListWithPermission($send_user);
               return true;
           }else
           {
               return false;
           }
           }
   }

    /*
   * 用户退出俱乐部
   */
    public function leaveClub($user_id,$club_id){
        //检测是否是某个俱乐部会员
        $member_ship = $this->getUserClubMembership($user_id,$club_id,0);
        if(isset($member_ship->member_id)&&$member_ship->status == 1)
        {
            $conditions = 'user_id ='.$user_id.' and club_id ='.$club_id.' and status = 1';
            $params = [
              $conditions,
            ];
            $member_info = (new \HJ\ClubMember())->findFirst($params);
            $member_info->status = 0;
            $res = $member_info->save();
            if(!$res)
            {
                $return = ['result'=> 1,'msg'=>'退出失败'];
                return $return;
            }
            $current_time = time();
            $type = 0;
            $sub_type = 1;
            $operate_user_id = $user_id;
            $process_user_id = $user_id ;
            $create_time = date("Y-m-d H:i:s",$current_time);
            $update_time = date("Y-m-d H:i:s",$current_time);
            $process_time = date("Y-m-d H:i:s",$current_time);
            $user_info = (new UserService())->getUserInfo($user_id,"user_id,company_id");
            $insert = new \HJ\ClubMemberLog();
            $insert->club_id = $club_id;
            $insert->user_id = $user_id;
            $insert->company_id = $user_info->company_id;
            $insert->type = $type;
            $insert->sub_type = $sub_type;
            $insert->operate_user_id = $operate_user_id;
            $insert->process_user_id = $process_user_id;
            $insert->result = 1;
            $insert->create_time = $create_time;
            $insert->update_time = $update_time;
            $insert->process_time = $process_time;
            $insert_result = $insert->create();
            if($insert_result)
            {
                $this->getUserClubMembership($user_id,$club_id,0);
                $this->getUserClubList($user_id,'member_id',1,0);
                $this->getUserClubListWithPermission($user_id);
                $return = ['result'=> 1,'msg'=>'退出成功'];
            }else
            {
                $return = ['result'=> 1,'msg'=>'退出失败'];
            }
        }else
        {
            $return = ['result'=> 1,'msg'=>'退出成功'];
        }
        return $return;
    }

    /*
     * 添加用户到俱乐部
     */
    public function addClubMember($params){
        $insert = (new \HJ\ClubMember())->create($params);
        return $insert;
    }

    public function getClubMemberLog($log_id){
        $params = [
            "log_id=".$log_id,
            'columns'=>'*'
        ];
        $club_member_log = (new \HJ\ClubMemberLog())->findFirst($params);
        return $club_member_log;
    }

    /*
     * 检测用户是否是管理员
     */
    public function getUserClubMembership($user_id = 0,$club_id = 0,$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->user_club_membership;
        $cacheName = $cacheSetting->name.$user_id."_".$club_id;
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
                    $this->redis->expire($cacheName, $cacheSetting->expire);
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
                $this->redis->expire($cacheName, $cacheSetting->expire);
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
            $this->redis->expire($cacheName, $cacheSetting->expire);

        }
        return $memberShip;
    }

    public function getUserClubPermission($user_id = 0,$club_id = 0,$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->user_club_permission;
        $cacheName = $cacheSetting->name.$user_id."_".$club_id;
        if($cache == 1)
        {
            $permission = $this->redis->get($cacheName);
            if(empty($permission))
            {
                //获取用户信息
                $userInfo = (new UserService())->getUserInfo($user_id,"user_id,manager_id");
                //超级管理员
                if($userInfo->manager_id>0)
                {
                    $permission = 99;
                }
                else
                {
                    //如果指定俱乐部
                    if($club_id>0)
                    {
                        //查询成员列表
                        $userClubMemberShip = $this->getUserClubMembership($user_id,$club_id,1);
                        $permission = $userClubMemberShip->permission??0;
                    }
                    else
                    {
                        $permission = 0;
                    }
                }
                $this->redis->set($cacheName, $permission);
                $this->redis->expire($cacheName, $cacheSetting->expire);
            }
            else
            {
                //
            }
        }
        else
        {
            //获取用户信息
            $userInfo = (new UserService())->getUserInfo($user_id,"user_id,manager_id");
            //超级管理员
            if($userInfo->manager_id>0)
            {
                $permission = 99;
            }
            else
            {
                //如果指定俱乐部
                if($club_id>0)
                {
                    //查询成员列表
                    $userClubMemberShip = $this->getUserClubMembership($user_id,$club_id,1);
                    $permission = $userClubMemberShip->permission??0;
                }
                else
                {
                    $permission = 0;
                }
            }
            $this->redis->set($cacheName, $permission);
            $this->redis->expire($cacheName, $cacheSetting->expire);
        }
        return $permission;
    }

    /*
     * 俱乐部成员列表
     * status 0 已不是成员 1俱乐部成员 2所有状态
     */
    public function getClubMemberList($club_id,$columns = "*",$start = 0,$page = 1,$pageSize =4,$status=2,$order = "member_id DESC"){


        if($status == 2)
        {
            $conditions = "club_id = ".$club_id;
        }else
        {
            $conditions = "club_id = ".$club_id.' and status ='.$status;
        }

        if($start)
        {
            $conditions.= ' and member_id <'.$start;
        }

        $params = [
                $conditions,
                "columns" => '*',
                "order" => $order,
                "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
            ];
            $memberList = (new \HJ\ClubMember())->find($params);

            $t = explode(",",$columns);
            $return = [];
            foreach($memberList as $key => $value)
            {
                foreach ($value as $k=>$v)
                {
                    if(!in_array($k,$t))
                    {
                        unset($memberList->$key->$k);
                    }
                }
            }

            return $memberList;
    }


    /*
     *申请记录列表
     * result 0 待审核 1已通过 2失败 3通过和失败  4所有状态
     */

    public function  getClubMemberLogInfo($club_id,$columns = "*",$start = 0,$page = 1,$pageSize =4,$result =4,$order = "log_id DESC"){
        if($result == 4)
        {
            $conditions = "club_id = ".$club_id;
        }elseif(is_array($club_id))
        {
            $club_ids = '';
            foreach ($club_id as $key =>$value)
            {
                $club_ids .=','.$value;
            }
            $club_ids =substr($club_ids,1);
            $club_ids ='('.$club_ids.')';
            $conditions = "club_id in ".$club_ids.' and ( result = 1 or result =2)';
        }else
        {
            $conditions = "club_id = ".$club_id.' and result ='.$result;
        }
        if($start)
        {
            $conditions.= ' and log_id >'.$start;
        }
        $params = [
            $conditions,
            "columns" => $columns,
            "order" => $order,
            "limit" => ["offset" => ($page - 1) * $pageSize, "number" => $pageSize]
        ];
        $memberList = (new \HJ\ClubMemberLog())->find($params);
        return $memberList;
    }
    public function getClubInfo($club_id,$columns = 'club_id,club_name,icon',$cache = 0)
    {
        $cacheSetting = $this->config->cache_settings->club_info;
        $cacheName = $cacheSetting->name.$club_id;
        $params =             [
            "club_id='".$club_id."'",
            'columns'=>'*',
        ];
        if($cache == 0)
        {
            //获取俱乐部信息
            $clubInfo = \HJ\Club::findFirst($params);
            if(isset($clubInfo->club_id))
            {
                $this->redis->set($cacheName,json_encode($clubInfo));
                $this->redis->expire($cacheName,3600);
                $clubInfo = json_decode($this->redis->get($cacheName));
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($cache->club_id))
            {
                $clubInfo = $cache;
            }
            else
            {
                //获取列表作者信息
                $clubInfo = \HJ\Club::findFirst($params);
                if(isset($clubInfo->club_id))
                {
                    $this->redis->set($cacheName,json_encode($clubInfo));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                    $clubInfo = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    return [];
                }
            }
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($clubInfo as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($clubInfo->$key);
                }
            }
        }

        return $clubInfo;
    }
    public function getClubListByCompany($company_id,$columns = 'club_id,club_name',$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->club_list_by_company;
        $cacheName = $cacheSetting->name.$company_id;
        $params =             [
            "company_id='".$company_id."'",
            'columns'=>'club_id',
            'order' => 'club_id DESC'
        ];
        if($cache == 0)
        {
            //获取俱乐部列表
            $clubList = \HJ\Club::find($params);
            if($clubList)
            {
                $this->redis->set($cacheName,json_encode($clubList));
                $this->redis->expire($cacheName,3600);
            }
            else
            {
                $clubList = [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if($cache)
            {
                $clubList = $cache;
            }
            else
            {
                //获取俱乐部列表
                $clubList = \HJ\Club::find($params);
                if($clubList)
                {
                    $this->redis->set($cacheName,json_encode($clubList));
                    $this->redis->expire($cacheName,3600);
                }
                else
                {
                    $clubList = [];
                }
            }
        }
        foreach($clubList as $key => $value)
        {
            $clubInfo = $this->getClubInfo($value->club_id,$columns);
            $clubList[$key]->clubInfo = $clubInfo;
        }
        return $clubList;
    }
    /*
     * 成员参加的俱乐部列表
     */
    public function getUserClubList($user_id,$columns = "*",$status=1,$cache =1){
        $cacheSetting = $this->config->cache_settings->user_club_list;
        $cacheName = $cacheSetting->name.$user_id;
        if($cache == 0)
        {
            $conditions = "user_id = ".$user_id.' and status ='.$status;
            $params = [
                $conditions,
                "columns" => '*',
                "order" => "member_id DESC",
            ];
            $clubList = (\HJ\ClubMember::find($params));
            if(count($clubList))
            {
                $this->redis->set($cacheName,json_encode($clubList));
                $this->redis->expire($cacheName,$cacheSetting->expire);
                $clubList = json_decode($this->redis->get($cacheName));

            }
            else
            {
                $clubList = [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if($cache)
            {
                $clubList = $cache;
            }
            else
            {
                $conditions = "user_id = ".$user_id.' and status ='.$status;
                $params = [
                    $conditions,
                    "columns" => '*',
                    "order" => "member_id DESC",
                ];
                $clubList = (\HJ\ClubMember::find($params));
                if(count($clubList))
                {
                    $this->redis->set($cacheName,json_encode($clubList));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                    $clubList = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    $clubList = [];
                }
            }
        }

        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($clubList as $key => $clubInfo)
            {
                $clubInfo = json_decode(json_encode($clubInfo),true);
                foreach($clubInfo as $k => $v)
                {
                    if (!in_array($k, $t))
                    {
                        unset($clubInfo[$k]);
                    }
                }
                $clubList[$key] = json_decode(json_encode($clubInfo));
            }
        }

        foreach($clubList as $key => $club)
        {
            if(!isset($club->club_id))
            {
                continue;
            }
            $clubInfo = $this->getClubInfo($club->club_id);
            if($clubInfo->club_id)
            {
                $clubList[$key]->clubInfo = $clubInfo;
            }
        }
        return $clubList;
    }

    /*
     * 查询俱乐部人数
     */
    public function getClubMemberCount($club_id = 0){
        $number = (new HJ\ClubMember())->query()->where("club_id =".$club_id)->andWhere('status = 1')->execute()->count();
        return $number;
    }

    /*
     * 拥有权限的俱乐部列表
     */
    public function getUserClubListWithPermission($user_id)
    {
        $userClubList = $this->getUserClubList($user_id,"member_id,club_id,permission",1,0);
        foreach ($userClubList as $key=>$club_info)
        {
            if($club_info->permission==0)
            {
                unset($userClubList[$key]);
            }
            else
            {
                $userClubList[$key] = (object)array_merge((array)$club_info,(array)$club_info->clubInfo);
                unset($userClubList[$key]->clubInfo);
            }
        }
        $userInfo = (new UserService())->getUserInfo($user_id,"user_id,company_id,manager_id");
        if($userInfo->manager_id > 0)
        {
            $currentClubList = (array_column($userClubList,'club_id'));
            $clubList = $this->getClubListByCompany($userInfo->company_id);
            foreach($clubList as $key => $clubInfo)
            {
                $clubInfo->permission = 9;
                if(!in_array($clubInfo->club_id,$currentClubList))
                {
                    $club_info = $this->getClubInfo($clubInfo->club_id);
                    $userClubList[] = array_merge((array)($clubInfo),(array)$club_info);
                }
            }
            if(count($clubList)==0)
            {
                $userClubList[] = ["permission"=>9];
            }
        }
        $userClubList = json_decode(json_encode($userClubList));
        return ($userClubList);
    }

    /*
     * 查询用户是否是俱乐部成员
     */
    public function checkUserIsClubMember($user_id,$club_id){
        $params = [
            'user_id ='.$user_id.' and club_id ='.$club_id.' and status = 1',
            'columns'=>'member_id'
        ];
        $member_info = (new \HJ\ClubMember())->findFirst($params);
        if($member_info)
        {
            return 1;
        }
        return 0;
    }

    /*
     * 获取俱乐部管理员列表
     */
    public function getClubManagerList($club_id){
        $conditions = 'club_id ='.$club_id.' and permission >0 and status = 1';
        $club_info = $this->getClubInfo($club_id,'club_id,company_id');
        //查询出超级管理员
        $manager_conditions = 'company_id ='.$club_info->company_id.' and manager_id>0';
        $manager_params = [
            $manager_conditions,
            'columns' =>'user_id'
        ];
        $manager_list =(new \HJ\UserInfo())->find($manager_params)->toArray();
        $params = [
            $conditions,
            "columns" => 'member_id,user_id'
        ];
        $user_list_info = (new \HJ\ClubMember())->find($params)->toArray();
        $user_list = [];
        foreach ($manager_list as $value)
        {
            $user_list []= $value['user_id'];
        }
        foreach ($user_list_info as $value)
        {
            if(in_array($value['user_id'],$user_list))
            {
                continue;
            }
            $user_list []= $value['user_id'];
        }
        return $user_list;
    }
    /*
     * 获取用户正在审核的俱乐部列表
     */
    public function getUserElationClub($user_id){
        $conditions = 'user_id ='.$user_id.' and result = 0';
        $params = [
            $conditions,
            'columns'=>'log_id,club_id'
        ];
        $club_member_logs = (new \HJ\ClubMemberLog())->find($params);
        return $club_member_logs;
    }

    /*
     * 俱乐部活动总次数统计
     */
    public function getClubActivityCount($club_id){
        $conditions = 'club_id ='.$club_id;
        $params = [
            $conditions,
            'columns'=>"count('activity_id')"
        ];
        $count = (new \HJ\Activity())->findFirst($params);
        return $count->{0};
    }
}