<?php

if(!isset($argv[1])) die("Run unitconvert with dictionary or downvote as argument");

$commands[] = new \JariZ\DownvoteMonitor();
$commands[] = new \JariZ\Dictionary();

foreach($commands as $command) {
    /* @var $command JariZ\Command */
    if($command->getName() == $argv[1]) {
        if(isset($argv[2]))
            $command->input = str_replace("\"", "", $argv[2]);
        $command->fire();
        break;
    }
}

die("Invalid argument");