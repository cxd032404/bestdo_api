<?php


namespace HJ;


use Phalcon\Mvc\Model;

class Source extends Model
{
    public function initialize()
    {
        $this->setSource("config_source");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}