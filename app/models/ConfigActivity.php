<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class ConfigActivity extends Model
{
    public function initialize()
    {
        $this->setSource("config_activity");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
