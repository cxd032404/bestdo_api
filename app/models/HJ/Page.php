<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Page extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("config_page");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
