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
    private $tempList = [
        "register_"=>"SMS_190729866",
        "login_"=>"SMS_190729866",
        "forget_"=>"SMS_190729866"
    ];

    private $msgList = [
        "mobile_error"=>"手机格式错误，请填写正确的手机号码！",
        "sendcode_many"=>"验证码发送次数过于频繁，请稍后再试！",
        "sms_insert_error"=>"短信发送记录新增失败！",
    ];



    //注册发送短信验证码方法
    public function sendRegisterCode($mobile,$code_name)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        $register_code = $this->redis->get($code_name.$mobile);
        $common = new Common();
        if(!isset($mobile) || !$common->check_mobile($mobile)){
            $return['msg']  = $this->msgList['mobile_error'];
        }else if($register_code){//判断redis缓存内是否存在记录，如存在则返回成功，不存在调用发送代码发送验证码
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendHour($mobile)){//判断验证码每小时内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendDay($mobile)){//判断验证码每天内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else{
            $data['phoneNumber'] = $mobile;//获取目标手机号
            $data['templateCode'] = $this->tempList[$code_name];//获取短信模板CODE
            $authCodeMT = mt_rand(100000,999999);//6位随机验证码
            $data['jsonTemplateParam'] = json_encode(['code'=>$authCodeMT]);//模板变量json字符串
            //调用阿里云发送短信
            $return_sms =  $this->sendAliDaYuAuthCode($data);
            if($return_sms['Code'] == "OK"){
                //短信发送记录存入数据库中
                $sendcode = new SendCode();
                $sendcode->to = $mobile;
                $sendcode->type = "mobile";
                $sendcode->code = $authCodeMT;
                if ($sendcode->create() === false) {
                    $return['msg']  = $this->msgList['sms_insert_error'];
                }else{
                    $return  = ['result'=>1,'msg'=>$return_sms['Message'],"data"=>$data["jsonTemplateParam"],'code'=>200];
                }
            }else{
                $return['msg']  = $return_sms['Message'];
            }
        }
        return $return;
    }

    //登录发送短信验证码方法
    public function sendLoginCode($mobile,$code_name)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        $login_code = $this->redis->get($code_name.$mobile);
        $common = new Common();
        if(!isset($mobile) || !$common->check_mobile($mobile)){
            $return['msg']  = $this->msgList['mobile_error'];
        }else if($login_code){//判断redis缓存内是否存在记录，如存在则返回成功，不存在调用发送代码发送验证码
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendHour($mobile)){//判断验证码每小时内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendDay($mobile)){//判断验证码每天内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else{
            $data['phoneNumber'] = $mobile;//获取目标手机号
            $data['templateCode'] = $this->tempList[$code_name];//获取短信模板CODE
            $authCodeMT = mt_rand(100000,999999);//6位随机验证码
            $data['jsonTemplateParam'] = json_encode(['code'=>$authCodeMT]);//模板变量json字符串
            //调用阿里云发送短信
            $return_sms =  $this->sendAliDaYuAuthCode($data);
            if($return_sms['Code'] == "OK"){
                //短信发送记录存入数据库中
                $sendcode = new SendCode();
                $sendcode->to = $mobile;
                $sendcode->type = "mobile";
                $sendcode->code = $authCodeMT;
                if ($sendcode->create() === false) {
                    $return['msg']  = $this->msgList['sms_insert_error'];
                }else{
                    $return  = ['result'=>1,'msg'=>$return_sms['Message'],'data'=>$data['jsonTemplateParam'],'code'=>200];
                }
            }else{
                $return['msg']  = $return_sms['Message'];
            }
        }
        return $return;
    }

    //忘记密码发送短信验证码方法
    public function sendForgetCode($mobile,$code_name)
    {
        $return = ['result'=>0,'data'=>[],'msg'=>"",'code'=>400];
        $forget_code = $this->redis->get($code_name.$mobile);
        $common = new Common();
        if(!isset($mobile) || !$common->check_mobile($mobile)){
            $return['msg']  = $this->msgList['mobile_error'];
        }else if($forget_code){//判断redis缓存内是否存在记录，如存在则返回成功，不存在调用发送代码发送验证码
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendHour($mobile)){//判断验证码每小时内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else if(!$this->checkoutSendDay($mobile)){//判断验证码每天内限制
            $return['msg']  = $this->msgList['sendcode_many'];
        }else{
            $data['phoneNumber'] = $mobile;//获取目标手机号
            $data['templateCode'] = $this->tempList[$code_name];//获取短信模板CODE
            $authCodeMT = mt_rand(100000,999999);//6位随机验证码
            $data['jsonTemplateParam'] = json_encode(['code'=>$authCodeMT]);//模板变量json字符串
            //调用阿里云发送短信
            $return_sms =  $this->sendAliDaYuAuthCode($data);
            if($return_sms['Code'] == "OK"){
                //短信发送记录存入数据库中
                $sendcode = new SendCode();
                $sendcode->to = $mobile;
                $sendcode->type = "mobile";
                $sendcode->code = $authCodeMT;
                if ($sendcode->create() === false) {
                    $return['msg']  = $this->msgList['sms_insert_error'];
                }else{
                    $return  = ['result'=>1,'msg'=>$return_sms['Message'],'data'=>$data['jsonTemplateParam'],'code'=>200];
                }
            }else{
                $return['msg']  = $return_sms['Message'];
            }
        }
        return $return;
    }

    //限制短信发送次数
    public function checkoutSendHour($mobile){
        $sendcode_hour = SendCode::count([
            "to=:to: and type='mobile' and create_time>:starttime:",
            'bind'=>['to'=>$mobile,'starttime'=>date('Y-m-d H:i:s',time()-3600)]
        ]);
        if($sendcode_hour>=5){
            return false;
        }
        return true;
    }

    //限制短信发送次数
    public function checkoutSendDay($mobile){
        $sendcode_day = SendCode::count([
            "to=:to: and type='mobile' and create_time>:starttime:",
            'bind'=>['to'=>$mobile,'starttime'=>date('Y-m-d H:i:s',time()-3600*24)]
        ]);
        if($sendcode_day>=10){
            return false;
        }
        return true;
    }








    /**
     * 集成方法：阿里云(原大鱼)发送短信验证码
     * @param string $phoneNumber 目标手机号
     * TODO 注意 accessKeyId、accessSecret、signName、templateCode 重要参数的获取配置
     */
    public function sendAliDaYuAuthCode($data)
    {
        $accessKeyId = $this->key_config->aliyun->sms->accessKeyId;
        $accessSecret = $this->key_config->aliyun->sms->accessSecret; //注意不要有空格
        $signName = $this->key_config->aliyun->sms->signName; //配置签名
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