<?php
use Predis\Client;
use Phalcon\Logger\Formatter\Line as PhFormatterLine;
use Phalcon\Logger\Adapter\File as PhFileLogger;

/*
|--------------------------------------------------------------------------
| Task
|--------------------------------------------------------------------------
|
| This value is the name of your application. This value is used when the
| framework needs to place the application's name in a notification or
| any other location as required by the application or its packages.
|
*/
ini_set ('memory_limit', '2048M');
use Phalcon\DI\FactoryDefault\CLI as CliDI,
    Phalcon\CLI\Console as ConsoleApp,
	Phalcon\Config as PhConfig;

error_reporting(9);
date_default_timezone_set('Asia/Shanghai');

define('VERSION', '1.0.0');
// 定义应用目录路径
defined('ROOT_PATH') || define('ROOT_PATH', __DIR__);

// 使用CLI工厂类作为默认的服务容器
$di = new CliDI();

//加载配置文件////////////////////////////////////////////////////////////////////////////////////////////////////////
$data = require_once (ROOT_PATH . '/configs/inc_config.php');
require_once ROOT_PATH . "/vendor/autoload.php";
$config = new PhConfig($data);
$di -> set('config', $config);

//加载key配置文件////////////////////////////////////////////////////////////////////////////////////////////////////////
$key_data = require_once (ROOT_PATH . '/configs/inc_key_config.php');
$key_config = new PhConfig($key_data);
$di -> set('key_config', $key_config);

//注册类自动加载器////////////////////////////////////////////////////////////////////////////////////////////////////////
$loader = new \Phalcon\Loader();
$loader->registerDirs($data['autoload']);
$loader->register();

//缓存服务///////////////////////////////////////////////////////////////////////////////////////////////
initRedis($di,$di['config']);
//数据库服务///////////////////////////////////////////////////////////////////////////////////////////////
initDatabase($di,$di['config']);
//日志服务///////////////////////////////////////////////////////////////////////////////////////////////
initLogger($di,$di['config']);
//curl
initWebCurl($di,$di['config']);
// 公共的函数库 Common 服务///////////////////////////////////////////////////////////////////////////////////////////////
$di->set('util', function ()  {
    return new Utilitys();
});

/*
// Redis 服务///////////////////////////////////////////////////////////////////////////////////////////////
$di->set('redis', function () use ( $config ) {
    return new WebRedis();
});
*/

//日志服务///////////////////////////////////////////////////////////////////////////////////////////////
/*
$di -> set('logger', function($filename='') use ($config) {
	$format   = $config->get('logger')->format;
    $filename = trim($config->get('logger')->filename, '\\/');
	$path = rtrim($config -> get('logger') -> path, '\\/') . DIRECTORY_SEPARATOR;
	$formatter = new Phalcon\Logger\Formatter\Line($format, $config -> get('logger') -> date);
	$logger = new Phalcon\Logger\Adapter\File($path . $filename);
	$logger -> setFormatter($formatter);
	return $logger;
});
*/

//创建console应用////////////////////////////////////////////////////////////////////////////////////////////////////////
$console = new ConsoleApp();
$console -> setDI($di);

//处理console应用参数  ////////////////////////////////////////////////////////////////////////////////////////////////////////
$arguments = [];
foreach ($argv as $k => $arg) {
    if ($k == 1) {
        $arguments['task'] = $arg;
    } elseif ($k == 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

//定义全局的参数， 设定当前任务及动作  /////////////////////////////////////////////////////////////////////////////////////////////////////
define('CURRENT_TASK', (isset($argv[1]) ? $argv[1] : null));
define('CURRENT_ACTION', (isset($argv[2]) ? $argv[2] : null));

// 处理参数   /////////////////////////////////////////////////////////////////////////////////////////////////////
try {
     $console->handle($arguments);
	 exit(0);
} catch (\Phalcon\Exception $e) {
	 $dt = date('Y-m-d H:i:s');
	 $di->get('logger')->error("错误：[{$dt}] {$e->getMessage()}\n\n");
	 echo "错误：[{$dt}] {$e->getMessage()}\n\n";
     exit(255);
}
function initRedis( $di,$config )
{
    foreach($config as $k => $c)
    {
        if($c['adapter'] == "Redis")
        {
            $di->set($k,  function() use ( $c )
            {
                $r =  new Client([
                    'host'       => $c->host,
                    //'port'       => $c->port,
                ],['cluster'=>'redis',
                    'parameters' => [
                        'password' => $c->auth_password]
                ]);
                return $r;
            }
            );
        }
    }
    return $di;
}
function initLogger( $di,$config )
{
    foreach($config as $k => $c) {
        if($c['adapter'] == 'logger') {
            $di->set($k,
                function ($filename = null, $format = null) use ($c) {
                    $format = $format ?: $c->format;
                    $filename = trim($filename ?: $c->filename, '\\/');
                    $path = rtrim($c->path, '\\/') . DIRECTORY_SEPARATOR;
                    $formatter = new PhFormatterLine($format, $c->date);
                    $request_logger = new PhFileLogger($path . $filename);
                    $request_logger->setFormatter($formatter);
                    $request_logger->setLogLevel($c->logLevel);
                    return $request_logger;
                });
        }
    }
    return $di;
}
function initDatabase( $di,$config )
{
    foreach($config as $k => $c)
    {
        if($c['adapter'] == "Mysql")
        {
            $di->set($k,  function() use ( $c )
            {
                    return new \Phalcon\Db\Adapter\Pdo\Mysql([
                    'host'       => $c->host,
                    'username'   => $c->username,
                    'password'   => $c->password,
                    'charset'    => 'UTF8',
                    'dbname'     => $c->dbname,
                    'persistent' => true
                ]);
            });
        }
    }
    return $di;
}
 function initWebCurl( $di,$config )
{
//    $this->di['curl'] = function() use ( $di ) {
//        return new WebCurl();
//    };
    $di->set('curl',  function() use ( $di )
    {
        return new WebCurl();
    });



}

