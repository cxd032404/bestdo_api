<?php
namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class StepsData extends Model
{
    public function initialize()
    {
        $this->setSource("user_steps_date");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
