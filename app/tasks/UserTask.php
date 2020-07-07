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
class UserTask extends \Phalcon\Cli\Task
{
	/**
	* test
	* @return {json}
	* @param  
	* @author huzhichao@laoyuegou.com
	* @link   
	* @date   
	*/
	public function generateAction(array $params)
	{
		try{
            $count = $params['1']??100;
		    $company_id = $params['0']??1;
		    (new UserService())->generateTestUser($company_id,$count);
            (new StepsService())->generateTestSteps($company_id,3);
            //(new StepsService())->generateTestSteps(date("m")-1);
            //(new StepsService())->generateTestSteps(date("m"));
        }catch (\Exception $e) {
		    $this->logger->error($e->getMessage());
		}
	}
}