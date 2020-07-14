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
     public function operateClubAction(){
         //验证token
         $return = (new UserService)->getDecrypt();
         if($return['result']!=1){
             return $this->failure([],$return['msg'],$return['code']);
         }
         $user_id = $return['data']['user_info']->user_id;
         $operation = $this->request->get('operation')??'';
         $log_id = $this->request->get('log_id')??[];
         $club_id = $this->request->get('club_id')??0;
         if($operation == 'join')
         {
             $res  = (new ClubService())->joinClub($user_id,$club_id);
         }
         elseif($operation == 'cancel')
         {
             $res  = (new ClubService())->applicationCancel($user_id,$log_id);
         }
         elseif($operation == 'pass' || $operation == 'reject')
         {
             $res  = (new ClubService())->ApplicationOperate($user_id,$operation,$log_id);
         }else
         {
             $res = ['result'=> 0,'msg'=>'操作类型有误'];
         }

         if($res['result'])
         {
             $this->success($res['data']??[],$res['msg']);
         }
         else
         {
             $this->failure([],$res['msg']);
         }
     }

     /*
      * 邀请用户加入俱乐部
      */
    public function inviteToClubAction(){
        //验证token
        $return = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $operate_user_id = $return['data']['user_info']->user_id;
        $user_id = $this->request->get('user_id')??0;
        $club_id = $this->request->get('club_id')??0;
        $comment = urldecode($this->request->get('comment'))??"";
        $return =  (new ClubService())->inviteToClub($operate_user_id,$user_id,$club_id,$comment);
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
     * 退出俱乐部
     */

    public function leaveClubAction(){
        //验证token
        $return = (new UserService)->getDecrypt();
        if($return['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $operate_user_id = $return['data']['user_info']->user_id;
        $user_id = $this->request->get('user_id')??0;
        $club_id = $this->request->get('club_id')??0;
        $reason = substr(trim($this->request->get('reason')??""),0,20);
        $return = (new ClubService())->leaveClub($operate_user_id,$user_id,$club_id,$reason);
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