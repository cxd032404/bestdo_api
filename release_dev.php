<?php
$sys = strtoupper(substr(PHP_OS, 0, 3));

$command = "git checkout dev && git status  && git pull";
(exec($command, $return));
echo implode("\n", $return) . "\n";
if($sys != "WIN")
{
    $command = "cd configs/ && cp inc_config_dev.php inc_config.php && cd ../ && ls";
}
else
{
    $command = "cd configs/ && copy inc_config_dev.php inc_config.php && cd ../ && dir";
}
(exec($command, $return));
echo implode("\n", $return) . "\n";
unset($return);
