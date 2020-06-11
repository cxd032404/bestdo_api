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
    public function operateApplication($user_id)
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
        if($this->request->get('operation')=='pass')
        {
            $club_member_log->result  = 1;
        }else
        {
            $club_member_log->result  = 2;
        }
        $club_member_log->operate_user_id = $user_id;
        $club_member_log->process_time = time();
        $res = $club_member_log->save();
        if($res)
        {
            $return = ['error_code'=> 0,'message'=>'通过成功'];
        }else
        {
            $return = ['error_code'=> 1,'message'=>'通过失败'];
        }

        return $return;

    }

    /*
     * 检测用户是否是管理员
     */

    public function checkMemberRole(){


    }



}