<?php
namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class ActivityListRank extends Model
{
    public function initialize()
    {
        $this->setSource("user_activity_list_rank");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
