<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Department extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("config_department");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
