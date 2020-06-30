<?php
/*
|--------------------------------------------------------------------------
| 公共函数类库
|--------------------------------------------------------------------------
|
| This value is the name of your application. This value is used when the
| framework needs to place the application's name in a notification or
| any other location as required by the application or its packages.
|
*/
use Phalcon\Mvc\User\Component;

class Common extends Component {
    
    /**
	 * 不从新排列键合并数组
	 * @param $arr1 array 需要合并的数组1 
	 */
	public static function merge($arr1, $arr2)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }
        return $res;
    }

    /**
	 * 解析XML
	 * @param $xml 
	 */
	public static function simplexml($xml) {

		libxml_disable_entity_loader(true); 
 
		$xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA); 
		 
		return json_decode(json_encode($xmlstring),true); 

	}

	/**
	 * Base64编码加密，可用于地址栏中传递
	 * @param $data string 需要加密的字符串
	 */
	static function base64url_encode($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * Base64编码解密，可用于地址栏中传递
	 * @param $data string 需要解密的字符串
	 */
	static function base64url_decode($data) {
		return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
	}

	/**
	 * 判断字符串是否Base64编码
	 * @param $str 需要判断的字符串
	 */
	static function is_base64($str) {
		return ($str == Common::base64url_encode(Common::base64url_decode($str)));
	}

	/**
	* 判断路径是否存在，不存在，将循环创建目录，并且权限是0766，有读写无执行权限，Window中无效
	* @param $path string 路径
	*/
	static function create_file_dir($path) {
		if (!file_exists($path)) {
			Common::create_file_dir(dirname($path));
			mkdir($path);
			//chmod($path, 0766);	// 正式使用这个
			chmod($path, 0777);  // 测试用这个
		}
	}

    /**
	 * 隐藏名等字符截取拼接方法
	 * @param $cardnum 需要隐藏的字符串
	 */
	static public function hidecard($cardnum, $type = 1, $default = "")
	{
	    if (empty($cardnum)) return $default;
	    if ($type == 1) $cardnum = substr($cardnum, 0, 3) . str_repeat("*", 12) . substr($cardnum, strlen($cardnum) - 4);   //身份证
	    elseif ($type == 2) $cardnum = substr($cardnum, 0, 3) . str_repeat("*", 5) . substr($cardnum, strlen($cardnum) - 4);    //手机号
	    elseif ($type == 3) $cardnum = str_repeat("*", strlen($cardnum) - 4) . substr($cardnum, strlen($cardnum) - 4);    //银行卡
	    elseif ($type == 4) {
	        $mb_str = mb_strlen($cardnum, 'UTF-8');
	        if ($mb_str <= 7) {
	            $suffix = mb_substr($cardnum, $mb_str - 1, 1, 'UTF-8');
	            $cardnum = mb_substr($cardnum, 0, 1, 'UTF-8') . str_repeat("*", 3) . $suffix;    //新用户名,无乱码截取
	        } else {
	            $suffix = mb_substr($cardnum, $mb_str - 3, 3, 'UTF-8');
	            $cardnum = mb_substr($cardnum, 0, 3, 'UTF-8') . str_repeat("*", 3) . $suffix;    //新用户名,无乱码截取
	        }
	    } elseif ($type == 5) {
	        $str = explode("@", $cardnum);
	        $cardnum = substr($str[0], 0, 2) . str_repeat("*", strlen($str[0]) - 2) . "@" . $str[1];  //邮箱
	    } elseif ($type == 6) $cardnum = mb_substr($cardnum, 0, 1, 'utf-8') . str_repeat("*", 3);    //真实姓名隐藏
	    elseif ($type == 7) $cardnum = substr($cardnum, 6, 4) . "-" . substr($cardnum, 10, 2) . "-" . substr($cardnum, 12, 2);    //出生日期
	    elseif ($type == 8) {
	        if (empty($cardnum)) {
	            $cardnum = "";
	        } else $cardnum = date('Y', time()) - substr($cardnum, 6, 4) . "岁";    //通过身份证号码获取用户年龄
	    } elseif ($type == 9) $cardnum = str_repeat("*", (strlen($cardnum) - 1) / 3) . mb_substr($cardnum, -1, 1, 'utf-8');    //紧急联系人姓名
	    elseif ($type == 10) { //通过身份证号码获取用户性别
	        $num = substr($cardnum, -2, 1);
	        if ($num % 2 == 0) {
	            $cardnum = "女";
	        } else {
	            $cardnum = "男";
	        }
	    }elseif ($type == 11) $cardnum = mb_substr($cardnum, 0, 1, 'utf-8') . str_repeat("", 3);    //真实姓名

	    return $cardnum;
	}

	/*正则验证手机号是否正确*/
	static function check_mobile($mobile) {
		if (!preg_match('/^1[0-9]{10}$/', $mobile)) {
			return false;
		}
		return true;
	}

    /**
     *转载自：http://www.jb51.net/article/56967.htm
     * @desc 根据两点间的经纬度计算距离
     * @param float $lat 纬度值
     * @param float $lng 经度值
     */
    static function getDistance($lat1, $lng1, $lat2, $lng2){
        $earthRadius = 6367000; //approximate radius of earth in meters
        $lat1 = ($lat1 * pi() ) / 180;
        $lng1 = ($lng1 * pi() ) / 180;
        $lat2 = ($lat2 * pi() ) / 180;
        $lng2 = ($lng2 * pi() ) / 180;
        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;
        return round($calculatedDistance);
    }
    /**
     * 生成日期段
     * @param string $dateRangeType 日期段类型范围  day日期 week周 month月 3month3个月 halfyear半年
     * @param string $dateType 日期端类型 1自然 2当前推

     */
    static function processDateRange($dateRangeType = "week",$dateType = 1)
    {
        $currentTime = time();
        if($dateRangeType == "day")
        {
            $return =  ["date" => date("Y-m-d",$currentTime)];
        }
        elseif($dateRangeType == "week")
        {
            if ($dateType == 1)
            {
                $s = date("w",$currentTime);
                if($s == 0)
                {
                    $s = 6;
                }
                else
                {
                    $s--;
                }
                $startDate = date("Y-m-d",$currentTime-($s)*86400);
                $endDate = date("Y-m-d",strtotime($startDate)+7*86400);
                $return =  ["startDate" => $startDate,"endDate" => $endDate];
            }
            else
            {
                $return =  ["startDate" => date("Y-m-d",$currentTime-7*86400),"endDate" => date("Y-m-d",$currentTime)];
            }
        }
        elseif($dateRangeType == "month")
        {
            if ($dateType == 1)
            {
                $s = date("w",$currentTime);
                $startDate = date("Y-m-01",$currentTime);
                $endDate = date("Y-m-t",$currentTime);
                $return =  ["startDate" => $startDate,"endDate" => $endDate];
            }
            else
            {
                $return =  ["startDate" => date("Y-m-d",$currentTime-30*86400),"endDate" => date("Y-m-d",$currentTime)];
            }
        }
        elseif($dateRangeType == "3month")
        {
            if ($dateType == 1)
            {
                $m = date("n",$currentTime);
                $startMonth = ceil($m/3)*3-2;
                $startDate = date("Y",$currentTime)."-".sprintf("%02d",$startMonth)."-01";
                $endDate = date("Y-m-t",strtotime("+2 month",strtotime($startDate)));
                $return =  ["startDate" => $startDate,"endDate" => $endDate];
            }
            else
            {
                $return =  ["startDate" => date("Y-m-d",$currentTime-(30*3-1)*86400),"endDate" => date("Y-m-d",$currentTime)];
            }
        }
        elseif($dateRangeType == "halfyear")
        {
            if ($dateType == 1)
            {
                $m = date("n",$currentTime);
                $startMonth = ceil($m/6)*6-5;
                $startDate = date("Y",$currentTime)."-".sprintf("%02d",$startMonth)."-01";
                $endDate = date("Y-m-t",strtotime("+5 month",strtotime($startDate)));
                $return =  ["startDate" => $startDate,"endDate" => $endDate];
            }
            else
            {
                $return =  ["startDate" => date("Y-m-d",$currentTime-(30*6-1)*86400),"endDate" => date("Y-m-d",$currentTime)];
            }
        }
        elseif($dateRangeType == "year")
        {
            if ($dateType == 1)
            {
                $startDate = date("Y-01-01",$currentTime);
                $endDate = date("Y-12-31",$currentTime);
                $return =  ["startDate" => $startDate,"endDate" => $endDate];
            }
            else
            {
                $return =  ["startDate" => date("Y-m-d",$currentTime-(365-1)*86400),"endDate" => date("Y-m-d",$currentTime)];
            }
        }
        $days = isset($return['date'])?1:(intval((strtotime($return['endDate'])-strtotime($return['startDate']))/86400)+1);
        $return["days"] = $days;
        return $return;
    }





}
