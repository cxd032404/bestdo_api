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
         $return  = (new UserService)->getDecrypt();
         if($return['result']!=1){
             return $this->failure([],$return['msg'],$return['code']);
         }
         $user_id = $return['data']['user_info']->user_id;
         $operation = $this->request->get('operation')??'';
         if($operation == 'join')
         {
             $res = (new ClubService())->joinClub($user_id);

         }elseif($operation == 'cancel')
         {
             //申请取消
         }elseif($operation == 'pass')
         {
             //通过申请
         }elseif($operation == 'reject')
         {
             //拒绝申请
         }else
         {
             //未知操作
         }
     }

     /*
      * 邀请用户加入俱乐部
      */
    public function inviteToClub(){

    }

    /*
     * 退出俱乐部
     */

    public function leaveClub(){

    }

    /*
     * 把成员踢出俱乐部
     */
    public function outClub(){


    }






}