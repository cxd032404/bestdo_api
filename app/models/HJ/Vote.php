<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Vote extends Model
{
    public $vote_id;
    public $name;
    public function initialize()
    {
        $this->setSource("config_vote");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
