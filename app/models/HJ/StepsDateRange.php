<?php


namespace HJ;


use Phalcon\Mvc\Model;

class StepsDateRange extends Model
{
    public function initialize()
    {
        $this->setSource("config_steps_date_range");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}