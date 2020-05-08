<?php
use HJ\Page;
use HJ\PageElement;
class PageService extends BaseService
{
	private $msg = 'success';

    //根据页面标示获取页面信息
    //$page_sign：页面标示
    public function getPageInfo($page_sign,$params = "")
	{
	    //获取页面信息
	    $pageInfo = $this->getPageBySign($page_sign);
	    //没如果获取到
	    if(!$pageInfo)
        {
            $return  = ['result'=>0,'code'=>404,'msg'=>"无此页面"];
        }
	    else
        {
            $params = json_decode($params,true);
            /*
            $p = $this->getFromParams($params);
            if(!$p)
            {
                echo "not found";
            }
            else
            {
                print_R($p);
            }
            */
            //转数组
            $pageInfo = $pageInfo->toArray();
            //获取页面元素详情
	        $pageElementLlist  = $this->getPageElementByPage($pageInfo['page_id'],"element_id,element_sign,element_type,detail",$params['element_sign_list']??[])->toArray();
	        //数组解包
	        foreach($pageElementLlist as $key => $elementDetail)
            {
                $pageElementLlist[$key]['detail'] = json_decode($elementDetail['detail'],true);
                if($elementDetail['element_type'] == "list")
                {
                    if(isset($pageElementLlist[$key]['detail']['list_id']))
                    {
                        $data = (new PostsService())->getPosts($pageElementLlist[$key]['detail']['list_id'],"*","post_id DESC",1,3);
                        print_R($data->toArray());
                    }
                }
            }
	        $return = ['result'=>1,'code'=>200,'data'=>['pageInfo'=>$pageInfo,'pageElementList'=>$pageElementLlist]];
        }
        return $return;
	}
    //根据页面标识获取页面
    //$page_sign：页面标识
    //cloumns：数据库的字段列表
    public function getPageBySign($page_sign,$columns = "page_id,page_name")
    {
        return (new Page())->findFirst(
            [
                "page_sign = '$page_sign'",
                "columns" => $columns
            ]);
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
    public function checkPageParams($params,$page_sign)
    {
        //获取页面信息
        $pageInfo = $this->getPageBySign($page_sign,'page_id,detail')->toArray();
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
        else
        {
            $return  = ['result'=>1];

        }
        return $return;
    }
    //从页面参数重获取数据
    //$params:页面参数json串
    //$param_name: 变量名  .表示层级
    public function getFromParams($params,$param_name = "user.family.sun")
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
                return false;
            }
        }
        return $params;
    }	
}