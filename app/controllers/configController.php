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
         $web = $this->request->get('web')??'wenti';
         $title_config_name = $web.'_title';
         $keyword_config_name = $web.'_keyword';
         $description_config_name = $web.'_description';

         $return = [];
         //标题
         $title_config_data = (new ConfigService())->getConfig($title_config_name);
         $return['title'] = $title_config_data->content;

         //关键词
         $keyword_config_data = (new ConfigService())->getConfig($keyword_config_name);
         $return['keyword'] = $keyword_config_data->content;
         //描述
         $description_config_data = (new ConfigService())->getConfig($description_config_name);
         $return['description'] = $description_config_data->content;
         return $this->success($return);

     }


}