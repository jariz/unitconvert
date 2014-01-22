<?php
require_once "BotConfig.php";
require_once "Bot.php";
require_once "DownvoteMonitor.php";

if(!isset($argv[1])) die("Run unitconvert with run or downvote as argument");

$commands[] = new \JariZ\Bot();
$commands[] = new \JariZ\DownvoteMonitor();

foreach($commands as $command) {
    /* @var $command Illuminate\Console\Command */
    if($command->getName() == $argv[1]) {
        $command->run(new \Symfony\Component\Console\Input\ArgvInput(array($argv[1])), new \Symfony\Component\Console\Output\ConsoleOutput());
        break;
    }
}

die("Invalid argument");