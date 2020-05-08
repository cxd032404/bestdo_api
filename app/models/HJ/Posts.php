<?php
namespace HJ;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Query;

class Posts extends Model
{
    public $post_id;
    public $list_id;
    public $content;
    public $source;
    public $create_time;
    public $end_time;
    public $user_id;

    public function initialize()
    {
        $this->setSource("user_posts");
        $this->setConnectionService("hj_user");
        $this->setReadConnectionService("hj_user");
        $this->setWriteConnectionService("hj_user");
    }
}
