<?php
// +----------------------------------------------------------------------
// | MainTask
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     MainTask.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
class WechatMessageTask extends \Phalcon\Cli\Task
{
    /*
 * 计划任务触发发送模板消息接口
 */
    public function sendWechatMessageAction(){
        try{
            (new WechatMessageService())->sendWechatMessage();
    }catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }
    }
}