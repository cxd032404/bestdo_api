<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class UserInfo extends Model
{
    public function initialize()
    {
        $this->setSource("user_info");
        $this->setConnectionService("database_1");
        $this->setReadConnectionService("database_1");
        $this->setWriteConnectionService("database_1");
    }
}
