<?php
namespace HJ;
use Phalcon\Mvc\Model;

class PageElement extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("config_page_element");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
