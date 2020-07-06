<?php


namespace HJ;


use Phalcon\Mvc\Model;

class Config extends Model
{
    public function initialize()
    {
        $this->setSource("config_default");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}