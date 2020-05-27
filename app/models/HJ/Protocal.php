<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Protocal extends Model
{
    public $protocal_id;
    public $company_id;
    public $type;
    public function initialize()
    {
        $this->setSource("config_protocal");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
