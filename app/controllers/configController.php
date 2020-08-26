<?php


class configController extends BaseController
{
    /*
     * 获取配置数据
     */
     public function getConfigAction(){

         $params =  $this->request->get('params');
         $params = json_decode($params,true);
         $data = [];


         foreach ($params as $key =>$value)
         {
              $config_data = (new ConfigService())->getConfig($value);
              $content = $config_data->content;
              $data[$value] = $content;
         }
         return $this->success($data);
     }



}