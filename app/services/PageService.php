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
	    $pageInfo = $this->getPageBySign($company_id,$page_sign);
	    //没如果获取到
	    if(!$pageInfo)
        {
            $return  = ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
	    else
        {
            $params = json_decode($params,true);
            //转数组
            $pageInfo = $pageInfo->toArray();
            //获取页面元素详情
	        $pageElementList  = $this->getPageElementByPage($pageInfo['page_id'],"element_id,element_name,element_sign,element_type,detail",$params['element_sign_list']??[])->toArray();
	        print_r($pageElementList);die();
	        foreach($pageElementList as $key => $elementDetail)
            {

                //数组解包
                $pageElementList[$key]['detail'] = json_decode($elementDetail['detail'],true);
                $functionName = "getElementPage_".$elementDetail['element_type'];
                $return = (new PageElementService())->$functionName($pageElementList[$key],$params,$user_info,$company_id);
                $pageElementList[$key] = $return;
            }
	        $pageElementList = array_combine(array_column($pageElementList,'element_sign'),array_values($pageElementList));
            $return = ['result'=>1,'code'=>200,'data'=>['pageInfo'=>$pageInfo,'pageElementList'=>$pageElementList]];
        }
        return $return;
	}
    //根据页面标识获取页面
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getPageBySign($company_id,$page_sign,$columns = "page_id,page_name")
    {
        $params =             [
            "page_sign = '$page_sign' and company_id = '$company_id'",
            "columns" => $columns
        ];
        return (new Page())->findFirst($params);
    }
	//根据页面ID获取元素列表
    //$page_id：页面ID
    //cloumns：数据库的字段列表
    //order：排序
	public function getPageElementByPage($page_id,$columns = "element_id,element_type",$element_sign_list = ["pic_2"],$order = "element_type DESC")
    {
        $params =             [
            //"page_id = ".$page_id,
            "columns" => $columns,
            "order" => $order,
            "bind" => ["elementSignList"=>$element_sign_list]
        ];
        if(count($element_sign_list))
        {
            $params[] = "page_id = $page_id and element_sign IN ({elementSignList:array})";
        }
        else
        {
            $params[] = "page_id = ".$page_id;
        }

        return (new \HJ\PageElement())->find(
            $params
        );
    }
    //检查页面参数是否完整和类型正确
    //$params:页面参数json串
    public function checkPageParams($params,$company,$page_sign)
    {
        //获取页面信息
        $pageInfo = $this->getPageBySign($company,$page_sign,'page_id,detail');
        if($pageInfo)
        {
            $pageInfo = $pageInfo->toArray();
        }
        else
        {
            return ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
        $pageInfo['detail'] = json_decode($pageInfo['detail'],true);
        if(isset($pageInfo['detail']['params']) && count($pageInfo['detail']['params'])>0)
        {
            $params = json_decode($params,true);
            $return = ['result'=>1,'detail'=>['lack'=>[],'error'=>[]]];
            foreach($pageInfo['detail']['params'] as $paramsInfo)
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