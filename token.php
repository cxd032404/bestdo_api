<?php
//define your token
define("TOKEN", "hj202004");//此处的TOKEN就是接下来需要填在微信的配置里面的token，需要保持严格一致
$wechatObj = new wechatCallbackapiTest();
$echoStr = $_GET["echostr"];
if($echoStr) {
	$wechatObj->valid();
}else
{
	$wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function responseMsg()
    {
		$postArr = file_get_contents('php://input', 'r');
		//获取到xml数据后，处理消息类型，并设置回复消息内容(回复就是直接打印xml数据)
		//数据格式
		$arr = $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
		if(strtolower($arr->MsgType)=="event")
		{
			$toUser = $arr->ToUserName;
			$foUser = $arr->FromUserName;
			$msgType = 'text';
			$createTime = time();
			$content = "感谢关注'文体之窗'。我们将竭诚为您服务，为您的企业丰富线上生活，提供员工舞台，管理健康大数据。";
			if(strtolower($arr->Event)=="subscribe")
			{//订阅
				$temp = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[%s]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
				$temp = sprintf($temp,$foUser,$toUser,$createTime,$msgType,$content);
				return $temp;
			}
		}
    }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
