<?php
// +----------------------------------------------------------------------
// | AccountService
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     AccountService.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Robots as robotModel;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\User\Component;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class SendCodeService extends BaseService
{
    //模板CODE
    private $tempList = ["register"=>"SMS_168340963","reset"=>"SMS_111111"];

    //注册发送短信验证码方法
    public function sendRegCode($mobile)
    {
        $common = new Common();
        if(!isset($mobile) || !$common->check_mobile($mobile))
            return $msg = ["code" => 0,"msg"=>"手机格式错误！"];
        $data['phoneNumber'] = $mobile;//获取目标手机号
        $data['templateCode'] = $this->tempList['register'];//获取短信模板CODE
        $authCodeMT = mt_rand(100000,999999);//6位随机验证码
        $data['jsonTemplateParam'] = json_encode(['code'=>$authCodeMT]);//模板变量json字符串
        //判断redis缓存内是否存在记录，如存在则返回成功，不存在调用发送代码发送验证码
        if($this->redis->get('register_'.$mobile)){
            return $msg = ["code" => 1,"msg"=>"验证码已发送，当前手机号发送次数过于频繁，请稍后再试！"];
        }
        //调用阿里云发送短信
        $return =  $this->sendAliDaYuAuthCode($data);
        if($return['Code'] == "OK"){
            //短信发送记录存入数据库中
            $sendcode = new SendCode();
            $sendcode->to = $mobile;
            $sendcode->type = "mobile";
            $sendcode->code = $authCodeMT;
            if ($sendcode->save() === false) {
                return $msg = ["code" => 0,"msg"=>"短信发送记录新增失败！"];
            }
            //短信验证码存入redis缓存中
            $this->redis->set('register_'.$mobile,$data["jsonTemplateParam"]);
            $this->redis->expire('register_'.$mobile,60);//设置过期时间,不设置过去时间时，默认为永久保持
            print_r($this->redis->get('register_'.$mobile));
            $msg = ["code" => 1,"msg"=>$return['Message']];
        }else{
            $msg = ["code" => 0,"msg"=>$return['Message']];
        }
        return $msg;
    }
    /**
     * 集成方法：阿里云(原大鱼)发送短信验证码
     * @param string $phoneNumber 目标手机号
     * TODO 注意 accessKeyId、accessSecret、signName、templateCode 重要参数的获取配置
     */
    public function sendAliDaYuAuthCode($data)
    {
        $accessKeyId = $this->config->aliyun_sms->accessKeyId;
        $accessSecret = $this->config->aliyun_sms->accessSecret; //注意不要有空格
        $signName = $this->config->aliyun_sms->signName; //配置签名
        $templateCode = $data['templateCode'];//配置短信模板编号
        $phoneNumber = $data['phoneNumber'];//目标手机号
        //TODO 短信模板变量替换JSON串,友情提示:如果JSON中需要带换行符,请参照标准的JSON协议。
        $jsonTemplateParam = $data['jsonTemplateParam'];

        AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)
            ->regionId('cn-hangzhou')
            ->asGlobalClient();
        try {
            $result = AlibabaCloud::rpcRequest()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'RegionId' => 'cn-hangzhou',
                        'PhoneNumbers' => $phoneNumber,
                        'SignName' => $signName,
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => $jsonTemplateParam,
                    ],
                ])
                ->request();
            $opRes = $result->toArray();
            return $opRes;
        } catch (ClientException $e) {
            return $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            return $e->getErrorMessage() . PHP_EOL;
        }
    }

	
}