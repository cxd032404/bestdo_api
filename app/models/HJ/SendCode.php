<?php

namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class SendCode extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("send_code");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
