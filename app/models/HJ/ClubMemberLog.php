<?php


namespace HJ;


use Phalcon\Mvc\Model;

class ClubMemberLog extends Model
{
    public function initialize()
    {
        $this->setSource("club_member_log");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}