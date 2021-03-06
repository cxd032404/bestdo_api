<?php
namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class UserInfo extends Model
{
    public $user_id;
    public function initialize()
    {
        $this->setSource("user_info");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
