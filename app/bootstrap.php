<?php
require_once "BotConfig.php";
require_once "Bot.php";

$app = new JariZ\Bot();
$app->run(new \Symfony\Component\Console\Input\ArgvInput(), new \Symfony\Component\Console\Output\ConsoleOutput());