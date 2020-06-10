<?php
/*
 * 2020/6/10
 * author shishuozheng
 */

class ClubService extends BaseService
{
    /*
     * 申请加入俱乐部
     */
  public function joinClub($user_id){
      $return = ['error_code'=> 0,'message'=>'申请成功'];
      $club_id = $this->request->get('club_id')??0;
      $params = [
          'user_id'=>$user_id,
          'club_id'=>$club_id,
          'status'=>1
      ];
      //判断是否已是俱乐部成员
      $club_info = (new \hj\ClubMember())->findfirst($params);
      if($club_info->user_id)
      {
          $return = ['error_code'=>1,'message'=>'已是俱乐部成员'];
          return $return;
      }
      $current_time = time();
      $company_id = $this->request->get('club_id')??0;
      $type = 1;
      $sub_type = 1;
      $operate_user_id = $user_id;
      $process_user_id = $user_id;
      $create_time = $current_time;
      $update_time = $current_time;

  }

  /*
   * 撤销加入俱乐部申请
   */
    public function cancelApplication(){



    }

    /*
     * 管理员通过用户申请操作
     */
    public function passApplication(){

    }
    /*
     * 管理员拒绝用户申请操作
     */
    public function rejectApplication(){

    }


}