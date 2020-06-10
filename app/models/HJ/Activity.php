<?php
namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Activity extends Model
{
    public function initialize()
    {
        $this->setSource("config_activity");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
