<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class UserCompany extends Model
{
    public function initialize()
    {
        $this->setSource("user_company");
        $this->setConnectionService("database_1");
        $this->setReadConnectionService("database_1");
        $this->setWriteConnectionService("database_1");
    }
}
