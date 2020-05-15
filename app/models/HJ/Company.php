<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Company extends Model
{
    public $company_id;
    public $company_name;
    public function initialize()
    {
        $this->setSource("config_company");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
