<?php
$command = "git checkout master && git status  && git pull";
(exec($command, $return));
echo implode("\n", $return) . "\n";
$command = "cd configs/ && cp inc_config_online.php inc_config.php && cd ../ && ls";
(exec($command, $return));
echo implode("\n", $return) . "\n";
unset($return);
