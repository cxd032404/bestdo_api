<?php
use HJ\Page;
use HJ\PageElement;
class PageService extends BaseService
{
	private $msg = 'success';

	public function getPageInfo($page_sign)
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
            //转数组
            $pageInfo = $pageInfo->toArray();
            //获取页面元素详情
	        $pageElementLlist  = $this->getPageElementByPage($pageInfo['page_id'],"element_id,element_sign,element_type,detail")->toArray();
	        //数组解包
	        foreach($pageElementLlist as $key => $elementDetail)
            {
                $pageElementLlist[$key]['detail'] = json_decode($elementDetail['detail'],true);
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
	public function getPageElementByPage($page_id,$columns = "element_id,element_type",$order = "element_type DESC")
    {
        return (new \HJ\PageElement())->find(
            [
                "page_id = ".$page_id,
                "columns" => $columns,
                "order" => $order
            ]
        );
    }
	
}