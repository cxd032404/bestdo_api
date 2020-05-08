<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class CompanyUserList extends Model
{
    public function initialize()
    {
        $this->setSource("company_user_list");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
