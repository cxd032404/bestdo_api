<?php
// +----------------------------------------------------------------------
// | MainTask
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     MainTask.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
class StepTask extends \Phalcon\Cli\Task
{
	/**
	* test
	* @return {json}
	* @param  
	* @author huzhichao@laoyuegou.com
	* @link   
	* @date   
	*/
	public function refreshAction(array $params)
	{
		try{
		    (new StepsService())->refreshStepsCache();

		
		}catch (\Exception $e) {
		    $this->logger->error($e->getMessage());
		}
	}
}