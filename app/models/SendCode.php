<?php

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class SendCode extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        $this->setSource("send_code");
        $this->setConnectionService("database_1");
        $this->setReadConnectionService("database_1");
        $this->setWriteConnectionService("database_1");

        $this->hasMany(
            'id',
            'RobotsParts',
            'robots_id'
        );
    }
}
