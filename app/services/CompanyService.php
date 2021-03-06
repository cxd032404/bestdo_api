<?php
use HJ\Company;
use HJ\Protocal;

class CompanyService extends BaseService
{
	private $msg = 'success';

    //根据id获取列表信息
    //$company_id：企业id
    //cloumns：数据库的字段列表
    public function getCompanyInfo($company_id,$columns = "company_name,company_id,icon,detail",$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->company_info;
        $cacheName = $cacheSetting->name.$company_id;
        $params =             [
            "company_id='".$company_id."'",
            'columns'=>'*',
        ];
        if($cache == 0)
        {
            //获取企业信息
            $company = \HJ\Company::findFirst($params);
            if(isset($company->company_id))
            {
                $this->redis->set($cacheName,json_encode($company));
                $this->redis->expire($cacheName,$cacheSetting->expire);
            }
            else
            {
                return [];
            }
        }
        else
        {
            $cache = $this->redis->get($cacheName);
            $cache = json_decode($cache);
            if(isset($cache->company_id))
            {
                $company = $cache;
            }
            else
            {
                //获取列表作者信息
                $company = \HJ\Company::findFirst($params);
                if(isset($company->company_id))
                {
                    $this->redis->set($cacheName,json_encode($company));
                    $this->redis->expire($cacheName,$cacheSetting->expire);
                }
                else
                {
                    return [];
                }
            }
        }
        $company = json_decode(json_encode($company),true);
        if($columns != "*")
        {
            $t = explode(",",$columns);
            $userInfo = json_decode(json_encode($company),true);
            foreach($company as $key => $value)
            {
                if(!in_array($key,$t))
                {
                    unset($company[$key]);
                }
            }
        }
        if(isset($company['detail']))
        {
            $company['detail'] = json_decode($company['detail'],true);
            $bannerTypeList = ["stepBanner","clubBanner","indexBanner","wtBanner"];
            foreach($bannerTypeList as $banner_type)
            {
                if(isset($company['detail'][$banner_type]))
                {
                    foreach ($company['detail'][$banner_type] as $key => $value) {
                        if (!is_array($value))
                        {
                            $source = \HJ\Source::findFirst(['source_id ='. $value])->toArray();
                            if (isset($source['source_id'])) {
                                $company['detail'][$banner_type][$key] = $source;
                            } else {
                                $source = $value;
                            }
                        }
                        $company['detail'][$banner_type][$key]['sort'] = $company['detail'][$banner_type][$key]['sort']??80;
                        if (isset($source['start_time']) && (time() < strtotime($source['start_time']) || time() > strtotime($source['end_time']))) {
                            unset($company['detail'][$banner_type][$key]);
                        }
                    }
                    $sort = array_column($company['detail'][$banner_type],"sort");
                    array_multisort($sort,SORT_ASC,$company['detail'][$banner_type]);
                }
                else
                {
                    $company['detail'][$banner_type] = [];
                }
            }
            $company['detail'] = json_encode($company['detail']);
        }
        $company = json_decode(json_encode($company));
        return $company;
    }
    //获取企业列表
    //cloumns：数据库的字段列表
    public function getCompanyList($columns = "company_name,company_id,icon")
    {
        return (new Company())->find(
            [
                "columns" => $columns
            ]);
    }
    //根据获取对应企业的协议信息
    //$company_id：企业id
    //$type：协议类型  user用户协议｜privacy用户政策
    //cloumns：数据库的字段列表
    public function getCompanyProtocal($company_id,$type = "user",$columns = "protocal_id,content")
    {
        $params =
        [
            "company_id = '".$company_id."' and type= '".$type."'",
            "columns" => $columns
        ];
        return (new Protocal())->findFirst($params);
    }
    //创建企业
    public function createCompany($companyInfo = [])
    {
        $company = new \HJ\Company();
        foreach($companyInfo as $key => $value)
        {
            $company->$key = $value;
        }
        $company->create_time = $company->update_time = date("Y-m-d H:i:s");
        if ($company->create() === true) {
            return ['result'=>true,"companyInfo"=>$company];
        }
        else
        {
            return ['result'=>false];
        }
        //ALTER TABLE `config_company` ADD `create_user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建人用户id' AFTER `parent_id`, ADD INDEX (`create_user_id`);
    }
    //更新企业信息
    public function updateCompanyInfo($company_id=0,$map)
    {
        //修改企业信息
        $companyInfo = \HJ\Company::findFirst(["company_id = '".$company_id."'"]);
        foreach($map as $key => $value)
        {
            if(!empty($value))
            {
                $companyInfo->$key = $value;
            }
        }
        $companyInfo->update_time = date("Y-m-d H:i:s");
        if ($companyInfo->update() === false) {
            return ['result'=>false];
        }else {
            $companyInfo = $this->getCompanyInfo($company_id,'*',0);
            return ['result'=>true,"companyInfo"=>$companyInfo];
        }
        return $return;
    }
    //根据公司名称获取公司信息

    public function getCompanyInfoByName($company_name,$columns = "company_name,company_id,icon,detail")
    {
        $params = [
            "company_name='" . $company_name . "'",
            'columns' => $columns,
        ];
        $company_info = \HJ\Company::findFirst($params);
        return $company_info;
    }

}