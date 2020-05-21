<?php
namespace HJ;
use Phalcon\Mvc\Model;

class Question extends Model
{
    public $questio_id;
    public $question;
    public $answer;
    public function initialize()
    {
        $this->setSource("config_question");
        $this->setConnectionService("hj_config");
        $this->setReadConnectionService("hj_config");
        $this->setWriteConnectionService("hj_config");
    }
}
