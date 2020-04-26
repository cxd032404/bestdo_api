<?php
use HJ\Page;
use HJ\PageElement;
class PageService extends BaseService
{
	private $msg = 'success';

	public function getPageBySign($page_sign)
	{
	    $pageInfo =
            (new Page())->findFirst(
	        [
	            "page_sign = '$page_sign'",
                "columns" => "page_id,page_name"
            ])->toArray();
	    if(!isset($pageInfo['page_id']))
        {
            $return  = ['result'=>0,'msg'=>"无此页面"];
        }
	    else {
	        $pageElementLlist  =
                (new \HJ\PageElement())->find(
	            [
	                "page_id = ".$pageInfo['page_id'],
                    "columns" => "element_id,element_sign,element_type,detail",
                    "order" => "element_type DESC"
                ]
            )->toArray();
	        foreach($pageElementLlist as $key => $elementDetail)
            {
                $pageElementLlist[$key]['detail'] = json_decode($elementDetail['detail'],true);
            }
	        $return['pageInfo'] = $pageInfo;
	        $return['pageElementList'] = $pageElementLlist;
        }
        return $return;
	}
	
}