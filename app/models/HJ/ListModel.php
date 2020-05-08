<?php
namespace HJ;
use Phalcon\Mvc\Model;

class ListModel extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("config_list");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
