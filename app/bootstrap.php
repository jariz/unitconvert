<?php

if(!isset($argv[1])) die("Run unitconvert with dictionary or downvote as argument");

$commands[] = new \JariZ\DownvoteMonitor();
$commands[] = new \JariZ\Dictionary();

foreach($commands as $command) {
    /* @var $command JariZ\Command */
    if($command->getName() == $argv[1]) {
        $command->fire();
        break;
    }
}

die("Invalid argument");