<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class ConfigCompany extends Model
{
    public function initialize()
    {
        $this->setSource("config_company");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
