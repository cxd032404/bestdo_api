<?php


namespace HJ;


use Phalcon\Mvc\Model;

class WebsiteMobile extends Model
{
    public function initialize()
    {
        $this->setSource("website_mobile_log");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");

    }
}