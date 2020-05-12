<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class UserActivitySign extends Model
{
    public function initialize()
    {
        $this->setSource("user_activity_sign");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
