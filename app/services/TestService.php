<?php
// +----------------------------------------------------------------------
// | AccountService
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     AccountService.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Robots;
class TestService extends BaseService
{
	private $msg = 'success';

	public function test()
	{
        $oRobot = new Robots();
	    //$oRobot->test();
        $robot = $oRobot->findAll(777)->toArray();

        print_R($robot);
        die();
        	$return = $this->database->fetchAll("show tables;");
        $return1 = $this->database_1->fetchAll("select * from test_table;");
        //print_R($return);print_R($return1);//die();
        $redis = $this->redis->incr("redis-test");
        //echo "redis:".$redis."\n";//phpinfo();
        //die();
        $yac = new Yac();
        $key = 'key';
        $yac->set( $key, ['id' => 111111]);

        $ret = $yac->get($key);

        //echo "<pre>";print_r( $yac->info() );exit;
        $return = ["ret" => $ret,'redis'=>$redis,"r1"=>$return1,"r"=>$return,"msg"=>$this->msg];
        return $return;
	}
	
}