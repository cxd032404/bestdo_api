<?php
use HJ\ListModel;
class ListService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListInfo($list_id,$columns = "list_id,company_id,detail",$cache = 1)
    {
        $cacheSettings = $this->config->cache_settings;
        $cacheName = $cacheSettings->list->name.$list_id;
        if($cache == 1)
        {
            $cacheData = $this->redis->get($cacheName);
            $cacheData = json_decode($cacheData);
            if(isset($cacheData->list_id))
            {
                $return = $cacheData;
            }else
            {
                $listData = (new ListModel())->findFirst(
                    [
                        "list_id = $list_id",
                        "columns" => '*'
                    ]);
                $this->redis->set($cacheName,json_encode($listData));
                $this->redis->expire($cacheName,$cacheSettings->list->expire);
                $return = $listData;
            }
        }else
        {
            $listData = (new ListModel())->findFirst(
                [
                    "list_id = $list_id",
                    "columns" => '*'
                ]);
            $this->redis->set($cacheName,json_encode($listData));
            $this->redis->expire($cacheName,$cacheSettings->list->expire);
            $return = $listData;
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($return as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($return->$key);
                }
            }
        }
        return $return;
    }


    //根据id获取列表信息
    //$list_id：列表id
    //cloumns：数据库的字段列表
    public function getListByActivity($activity_id,$columns = "list_id,company_id,detail")
    {
        return (new ListModel())->find(
            [
                "activity_id = $activity_id",
                "columns" => $columns
            ]);
    }

    public function processAfterPostAction($list_id,$user_id,$detail)
    {
        $return = ['after_url'=>'','params'=>[]];
        if(isset($detail['after_action'])) {
            $detail['after_action'] = $detail['after_action']??"";
        }if(isset($detail['after_url']))
        {
            $detail['after_url'] = str_replace("#list_id#",$list_id,($detail['after_url']??""));
            $detail['after_url'] = str_replace("#user_id#",$user_id,($detail['after_url']??""));
            $t = explode("|",$detail['after_url']??"");

            foreach($t as $key => $value)
            {
                if($key==0)
                {
                    $return['after_url'] = trim($value);
                }
                else
                {
                    $t2 = explode("=",trim($value));
                    $return['params'][] = ["key"=>trim($t2[0]),'value'=>trim($t2[1]??"")];
                }
            }
        }

        return $return;
    }
}