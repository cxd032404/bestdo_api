<?php


class configController extends BaseController
{
    /*
     * 获取配置数据
     */
     public function getConfigAction(){

         $params =  $this->request->get('params');
         $params = json_decode($params,true);
         foreach ($params as $key =>$value)
         {
              $config_data = (new ConfigService())->getConfig($value);
              $content = $config_data->content;
              $data[$value] = $content;
         }
         return $this->success($data);
     }
     /*
      * 获取官网tdk
      */

     public function getTdkAction(){
         $params = $this->request->get('web');
         $title_config_name = $params


     }


}