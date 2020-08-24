<?php
use HJ\Activity;
use HJ\UserInfo;
class ConfigService extends BaseService
{
    public function getConfig($config_sign,$cache = 1)
    {
        $cacheSetting = $this->config->cache_settings->config;
        $cacheName = $cacheSetting->name.$config_sign;
        $params =             [
            "config_sign = '".$config_sign."'",
            "columns" => '*',
        ];
        if($cache == 1)
        {
            $configCache = $this->redis->get($cacheName);
            $configCache = json_decode($configCache);
            if(isset($configCache->config_sign))
            {
                $config = $configCache;
            }
            else
            {
                $config = (new \HJ\Config())->findFirst($params);
                if(isset($config->config_sign)) {
                    $this->redis->set($cacheName, json_encode($config));
                    $this->redis->expire($cacheName, $cacheSetting->expire);
                    $config = json_decode($this->redis->get($cacheName));
                }
                else
                {
                    $config = [];
                }
            }
        }
        else
        {
            $config = (new \HJ\Config())->findFirst($params);
            if(isset($config->config_sign)) {
                $this->redis->set($cacheName, json_encode($config));
                $this->redis->expire($cacheName, $cacheSetting->expire);
                $config = json_decode($this->redis->get($cacheName));
            }
            else
            {
                $config = [];
            }
        }
        if($config->config_type == source) {
            $source_array = json_decode($config->content, true);
            foreach ($source_array as $key => $value) {
                if (!is_array($value)) {
                    $source = \HJ\Source::findFirst(['source_id =' . $value])->toArray();
                    if (isset($source['source_id'])) {
                        $source_array[$key] = $source;
                    }
                }
            }
            $config->content = json_encode($source_array);
        }
        return $config;
    }
}