#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use DanielPieper\MergeReminder\Command\ConfigureCommand;
use DanielPieper\MergeReminder\Command\MergeRequestsCommand;
use DanielPieper\MergeReminder\Command\SlackCommand;
use Symfony\Component\Console\Application;

$app = new Application('mrcli', '@package_version@');

$app->add(new ConfigureCommand('configure'));
$app->add(new SlackCommand('slack'));
$app->add(new MergeRequestsCommand('merge-requests'));
$app->run();
