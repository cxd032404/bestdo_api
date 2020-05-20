<?php
$command = "git checkout dev && git status  && git pull";
(exec($command, $return));
echo implode("\n", $return) . "\n";
$command = "cd configs/ && cp inc_config_dev.php inc_config.php && cd ../ && ls";
(exec($command, $return));
echo implode("\n", $return) . "\n";
unset($return);
