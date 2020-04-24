<?php

use Phalcon\Mvc\Model;

class Robots extends Model
{
    public $id;
    public $name;
    public function initialize()
    {
        echo "000";
        $this->setSource("test_table");
        $this->setConnectionService("database_1");
        $this->setReadConnectionService("database_1");
        $this->setWriteConnectionService("database_1");

        $this->hasMany(
            'id',
            'RobotsParts',
            'robots_id'
        );
    }
    public function test()
    {
        echo "999";
    }
}
