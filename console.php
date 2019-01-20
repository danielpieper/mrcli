#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use DanielPieper\MergeReminder\Command\ApproverCommand;
use DanielPieper\MergeReminder\Command\OverviewCommand;
use DanielPieper\MergeReminder\Command\ProjectCommand;
use Symfony\Component\Console\Application;

$dotEnv = \Dotenv\Dotenv::create(__DIR__);
$dotEnv->load();
$dotEnv->required(['GITLAB_TOKEN', 'SLACK_WEBHOOK_URL', 'SLACK_CHANNEL'])->notEmpty();

$container = new \League\Container\Container();
$container->add('SLACK_WEBHOOK_URL', getenv('SLACK_WEBHOOK_URL'));
$container->add('SLACK_CHANNEL', getenv('SLACK_CHANNEL'));
$container->add('GITLAB_TOKEN', getenv('GITLAB_TOKEN'));
$container->add('GITLAB_URL', getenv('GITLAB_URL') ?? 'https://gitlab.com');
$container->addServiceProvider(\DanielPieper\MergeReminder\ServiceProvider\GitlabServiceProvider::class)
    ->addServiceProvider(\DanielPieper\MergeReminder\ServiceProvider\SlackServiceProvider::class)
    ->addServiceProvider(\DanielPieper\MergeReminder\ServiceProvider\AppServiceProvider::class);

$app = new Application('mrcli', '@package_version@');
$app->add($container->get(OverviewCommand::class));
$app->add($container->get(ProjectCommand::class));
$app->add($container->get(ApproverCommand::class));
$app->setDefaultCommand($container->get(ApproverCommand::class)->getName());
$app->run();
