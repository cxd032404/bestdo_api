<?php
namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class WechatMessage extends Model
{
    public function initialize()
    {
        $this->setSource("wechatMessage");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
