<?php
use HJ\Page;
use HJ\PageElement;
class PageService extends BaseService
{
	private $msg = 'success';

    
    //根据页面标示获取页面信息
    //$page_sign：页面标示
    public function getPageInfo($company_id,$page_sign,$params = "",$user_info)
	{
	    //获取页面信息
	    $pageInfo = $this->getPageInfoBySign($company_id,$page_sign);
	    //没如果获取到
	    if(!$pageInfo)
        {
            $return  = ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
	    else
        {
            $params = json_decode($params,true);
            //获取页面元素详情
	        $pageElementList  = $this->getPageElementByPage($pageInfo->page_id,"element_sign,element_name,element_type,detail",$params['element_sign_list']??[]);
            foreach($pageElementList as $key => $elementDetail)
            {
                //数组解包
                $pageElementList[$key]['detail'] = json_decode($elementDetail['detail'],true);
                $functionName = "getElementPage_".$elementDetail['element_type'];
                if(method_exists(PageElementService::class,$functionName)) {
                    $return = (new PageElementService())->$functionName($pageElementList[$key], $params, $user_info, $company_id);
                    if(!$return) {
                        unset($pageElementList[$key]);
                        continue;
                    }
                        $pageElementList[$key] = $return;
                }
                else
                {
                    $pageElementList[$key] = $pageElementList[$key];

                }
            }
	        $pageElementList = array_combine(array_column($pageElementList,'element_sign'),array_values($pageElementList));
            $return = ['result'=>1,'code'=>200,'data'=>['pageInfo'=>$pageInfo,'pageElementList'=>$pageElementList]];
        }
        return $return;
	}
    //根据页面标识获取页面
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getPageInfoBySign($company_id,$page_sign,$columns = "page_id,page_name",$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->page_info_sign;
        $cacheName = $cacheSetting->name.$company_id."_".$page_sign;
        $params = [
            "page_sign = '$page_sign' and company_id = '$company_id'",
            "columns" => $columns
        ];
        if($cache == 0)
        {
            //获取页面信息
            $pageInfo = (new Page())->findFirst($params);
            if(isset($pageInfo->page_id))
            {
                $this->redis->set($cacheName,json_encode($pageInfo));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($pageInfo->page_id))
            {
                //获取页面信息
                $pageInfo = $cache;
            }
            else
            {
                //获取页面信息
                $pageInfo = (new Page())->findFirst($params);
                if(isset($pageInfo->page_id))
                {
                    $this->redis->set($cacheName,json_encode($pageInfo));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        $pageInfo = json_decode(json_encode($pageInfo),true);
        if($columns != "*")
        {
            $t = explode(",",$columns);
            $pageInfo = json_decode(json_encode($pageInfo),true);
            foreach($pageInfo as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($pageInfo[$key]);
                }
            }
        }
        $pageInfo = json_decode(json_encode($pageInfo));
        return $pageInfo;
    }
    //根据页面ID获取页面
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getPageInfoById($page_id,$columns = "page_id,page_name",$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->page_info_id;
        $cacheName = $cacheSetting->name.$page_id;
        $params = [
            "page_id = ".$page_id,
            "columns" => $columns
        ];
        if($cache == 0)
        {
            //获取页面信息
            $pageInfo = (new Page())->findFirst($params);
            if(isset($pageInfo->page_id))
            {
                $this->redis->set($cacheName,json_encode($pageInfo));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($pageInfo->page_id))
            {
                //获取页面信息
                $pageInfo = $cache;
            }
            else
            {
                //获取页面信息
                $pageInfo = (new Page())->findFirst($params);
                if(isset($pageInfo->page_id))
                {
                    $this->redis->set($cacheName,json_encode($pageInfo));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        $pageInfo = json_decode(json_encode($pageInfo),true);
        if($columns != "*")
        {
            $t = explode(",",$columns);
            $pageInfo = json_decode(json_encode($pageInfo),true);
            foreach($pageInfo as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($pageInfo[$key]);
                }
            }
        }
        $pageInfo = json_decode(json_encode($pageInfo));
        return $pageInfo;
    }
	//根据页面ID获取元素列表
    //$page_id：页面ID
    //cloumns：数据库的字段列表
    //order：排序
	public function getPageElementByPage($page_id,$columns = "element_id,element_type",$element_sign_list = ["pic_2"],$order = "element_type DESC",$cache = 0)
    {
        $cacheSetting = $this->config->cache_settings->page_element_list;
        $cacheName = $cacheSetting->name.$page_id;
        $params =             [
            "page_id = '".$page_id."'",
            "columns" => "*",
            "order" => $order,
        ];
        if($cache == 0)
        {
            //获取页面元素列表
            $pageElementList = (new \HJ\PageElement())->find($params);
            if(count($pageElementList)>0)
            {
                $this->redis->set($cacheName,json_encode($pageElementList));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(count($cache)>0)
            {
                $pageElementList = $cache;
            }
            else
            {
                //获取页面元素列表
                $pageElementList = (new \HJ\PageElement())->find($params);
                if(count($pageElementList)>0)
                {
                    $this->redis->set($cacheName,json_encode($pageElementList));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        $pageElementList = json_decode(json_encode($pageElementList),true);
        if(count($element_sign_list)>0)
        {
            foreach($pageElementList as $key => $value)
            {
                if(!in_array($value['element_sign'],$element_sign_list))
                {
                    unset($pageElementList[$key]);
                }
            }
        }
        if($columns != "*")
        {
            $t = explode(",",$columns);
            foreach($pageElementList as $key => $value)
            {
                foreach($value as $k => $v)
                {
                    if(!in_array($k,$t))
                    {
                        unset($pageElementList[$key][$k]);
                    }
                }
            }
        }
        return $pageElementList;
    }
    //检查页面参数是否完整和类型正确
    //$params:页面参数json串
    public function checkPageParams($params,$company,$page_sign)
    {
        //获取页面信息
        $pageInfo = $this->getPageInfoBySign($company,$page_sign,'page_id,detail');
        if($pageInfo)
        {
            //$pageInfo = json_decode(json_encode($pageInfo),true);
        }
        else
        {
            return ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
        $pageInfo->detail = json_decode($pageInfo->detail,true);
        if(isset($pageInfo->detail['params']) && count($pageInfo->detail['params'])>0)
        {
            $params = json_decode($params,true);
            $return = ['result'=>1,'detail'=>['lack'=>[],'error'=>[]]];
            foreach($pageInfo->detail['params'] as $paramsInfo)
            {
                if(!isset($params[$paramsInfo['name']]))
                {
                    $return['result'] = 0;
                    $return['code'] = 500;
                    $return['detail']['lack'][] = $paramsInfo['name'];
                }
                else
                {
                    if(in_array($paramsInfo['type'],['int']))
                    {
                        $function_name  = "is_".$paramsInfo['type'];
                        if(!$function_name($params[$paramsInfo['name']]))
                        {
                            $return['result'] = 0;
                            $return['code'] = 500;
                            $return['detail']['error'][] = $paramsInfo['name'];
                        }
                    }
                }
            }
        }
        else
        {
            $return  = ['result'=>1];

        }
        return $return;
    }
    //从页面参数重获取数据
    //$params:页面参数json串
    //$param_name: 变量名  .表示层级
    public function getFromParams($params,$param_name = "user.family.sun",$default = null)
    {
        $t = explode(".",$param_name);
        foreach($t as $key)
        {
            if(isset($params[$key]))
            {
                $params = $params[$key];
            }
            else
            {
                return $default;
            }
        }
        return $params;
    }
}