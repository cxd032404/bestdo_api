<?php


namespace HJ;


use Phalcon\Mvc\Model;

class Club extends Model
{
    public function initialize()
    {
        $this->setSource("config_club");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}