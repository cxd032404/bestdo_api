<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class CompanyUserList extends Model
{
    public function initialize()
    {
        $this->setSource("company_user_list");
        $this->setConnectionService("database_1");
        $this->setReadConnectionService("database_1");
        $this->setWriteConnectionService("database_1");
    }
}
