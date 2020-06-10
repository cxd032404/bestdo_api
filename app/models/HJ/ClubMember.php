<?php


namespace HJ;


use Phalcon\Mvc\Model;

class ClubMember extends Model
{
    public function initialize()
    {
        $this->setSource("club_member");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}