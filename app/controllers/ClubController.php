<?php
/*
 * 2020/6/10
 * author shishuozheng
 */

class ClubController extends BaseController
{


    /*
     * 加入俱乐部
     */
     public function joinClubAction(){
         //验证token
         $return = (new UserService)->getDecrypt();
         if($return['result']!=1){
             return $this->failure([],$return['msg'],$return['code']);
         }
         $user_id = $return['data']['user_info']->user_id;
         $operation = $this->request->get('operation')??'';
         $log_id = $this->request->get('log_id')??0;
         $club_id = $this->request->get('club_id')??0;
         if($operation == 'join')
         {
             $return  = (new ClubService())->joinClub($user_id,$club_id);
         }
         elseif($operation == 'cancel')
         {
             $return  = (new ClubService())->applicationCancel($user_id,$log_id);
         }
         elseif($operation == 'pass' || $operation == 'reject')
         {
             $return  = (new ClubService())->ApplicationOperate($user_id,$operation,$log_id);
         }

         if($return['result'])
         {
             $this->success($return['data']??[],$return['msg']);
         }
         else
         {
             $this->failure([],$return['msg']);
         }
     }

     /*
      * 邀请用户加入俱乐部
      */
    public function inviteToClubAction(){
        //验证token


    }

    /*
     * 退出俱乐部
     */

    public function leaveClubAction(){
        //验证token
        $return = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $user_id = $return['data']['user_info']->user_id;
        $club_id = $this->request->get('club_id')??0;
        $return = (new ClubService())->leaveClub(2,1);
        if($return['result'])
        {
            $this->success($return['data']??[],$return['msg']);
        }
        else
        {
            $this->failure([],$return['msg']);
        }
    }

    /*
     * 把成员踢出俱乐部
     */
    public function outClubAction(){

    }






}