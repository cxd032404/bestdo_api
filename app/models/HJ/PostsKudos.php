<?php

namespace HJ;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class PostsKudos extends Model
{
    public $post_id;
    public $user_id;
    public $create_time;

    public function initialize()
    {
        $this->setSource("user_posts_kudos");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
