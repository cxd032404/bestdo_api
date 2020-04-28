<?php
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Phalcon\Mvc\User\Component;

class ThirdJwt
{
    private static $_config = [
        'audience' => 'http://www.pyg.com',//接收人
        'id' => '3f2g57a92aa',//token的唯一标识，这里只是一个简单示例
        'sign' => 'pinyougou',//签名密钥
        'issuer' => 'http://adminapi.pyg.com',//签发人
        'expire' => 3600*24 //有效期
    ];

    //生成token
    public static function getToken($map){
        //签名对象
        $signer = new Sha256();
        //获取当前时间戳
        $time = time();
        //设置签发人、接收人、唯一标识、签发时间、立即生效、过期时间、用户id、签名
        $token = (new Builder())->issuedBy(self::$_config['issuer'])
            ->canOnlyBeUsedBy(self::$_config['audience'])
            ->identifiedBy(self::$_config['id'], true)
            ->issuedAt($time)
            ->canOnlyBeUsedAfter($time-1)
            ->expiresAt($time + self::$_config['expire'])
            ->with('data', json_encode($map))
            ->sign($signer, self::$_config['sign'])
            ->getToken();
        return (string)$token;
    }

    //从请求信息中获取token令牌
    public static function getRequestToken()
    {
        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return false;
        }
        $header = $_SERVER['HTTP_AUTHORIZATION'];
        $method = 'bearer';
        //去除token中可能存在的bearer标识
        return trim(str_ireplace($method, '', $header));
    }

    //从token中获取用户id （包含token的校验）
    public static function getUserId($token = null,$maps=[])
    {
        $user_info = null;
        $token = empty($token)?self::getRequestToken():$token;
        if (!empty($token)) {
            /*
            //为了注销token 加以下if判断代码
            $delete_token = cache('delete_token') ?: [];
            if(in_array($token, $delete_token)){
                //token已被删除（注销）
                return $user_id;
            }
            */
            try{
                $token = (new Parser())->parse((string) $token);
            } catch(Exception $e){
                return "";
            }
            //验证token
            $data = new ValidationData();
            $data->setIssuer(self::$_config['issuer']);//验证的签发人
            $data->setAudience(self::$_config['audience']);//验证的接收人
            $data->setId(self::$_config['id']);//验证token标识
            if (!$token->validate($data)) {echo 1111;
                //token验证失败
                return $user_info;
            }
            //验证签名
            $signer = new Sha256();
            if (!$token->verify($signer, self::$_config['sign'])) {
                //签名验证失败
                return $user_info;
            }
            //从token中获取用户id
            $user_info = $token->getClaim('data');
        }
        return $user_info;
    }
}

//TokenController 调用实现

/*
Class TokenController
{
    public function create()
    {

        $token = Token::createToken(1);
        var_dump($token);
    }

    public function validate($token)
    {
        $bool = Token::validateToken($token);
        var_dump($bool);
    }
}
*/