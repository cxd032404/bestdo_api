<?php


class MessageService extends BaseService
{
  public function sendMessage(){
      $redisKey = $this->config->redisQueue->wechatMessageQueue;

      for($i=50;$i<50;$i++) {
          $message_info = $send_message_info = $this->getMessage();
          if(!$message_info)
          {
              break;
          }
          $accessToken = (new WechatService())->checkWechatAccessToken();
          $res = (new WechatService())->sendWechatMessage($accessToken, $message_info['openid'], '-Qq05dZSlDIf7LyuSWf0V3tJ9AuXjypdempKDTSGUio', $message_info['content']);
          $this->logger->info(json_encode($res));
          //发送失败 塞回队列
          if (!isset($res['errcode']) || $res['errcode']) {
              $this->redis->rpush($redisKey, json_encode($message_info));
          }
      }

  }

    //从队列获取信息
    private function getMessage(){
        $redisKey = $this->config->redisQueue->wechatMessageQueue;
        $res = $this->redis->lpop($redisKey);
        return json_decode($res);
    }

}