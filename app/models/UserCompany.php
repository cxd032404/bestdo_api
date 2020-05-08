<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class UserCompany extends Model
{
    public function initialize()
    {
        $this->setSource("user_company");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
