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
         $res = (new UserService)->getDecrypt();
         if($res['result']!=1){
             return $this->failure([],$return['msg'],$return['code']);
         }
         $user_id = $res['data']['user_info']->user_id;
         $company_id = $res['data']['user_info']->company_id;
         $operation = $this->request->get('operation')??'';
         if($operation == 'join')
         {
             $return  = (new ClubService())->joinClub($user_id,$company_id);

         }elseif($operation == 'cancel')
         {
             $return  = (new ClubService())->cancelApplication($user_id);
         }elseif($operation == 'pass')
         {
             $return  = (new ClubService())->operateApplication($user_id,$company_id);
         }elseif($operation == 'reject')
         {
             //未知操作
         }
         return $this->success($return);
     }

     /*
      * 邀请用户加入俱乐部
      */
    public function inviteToClub(){
        //验证token
        $res = (new UserService)->getDecrypt();
        if($res['result']!=1){
            return $this->failure([],$return['msg'],$return['code']);
        }
        $user_id = $res['data']['user_info']->user_id;
        $company_id = $res['data']['user_info']->company_id;
        $operation = $this->request->get('operation')??'';
        if($operation == 'join')
        {
            $return  = (new ClubService())->joinClub($user_id,$company_id);

        }elseif($operation == 'cancel')
        {
            $return  = (new ClubService())->cancelApplication($user_id);
        }elseif($operation == 'pass'||$operation == 'reject')
        {
            $return  = (new ClubService())->operateApplication($user_id);
        }else
        {
            //未知操作
        }
        return $this->success($return);

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