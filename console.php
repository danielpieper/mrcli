#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use DanielPieper\MergeReminder\Command\DefaultCommand;
use Symfony\Component\Console\Application;

$dotEnv = new \Dotenv\Dotenv(__DIR__);
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
$app->add($container->get(DefaultCommand::class));
$app->setDefaultCommand($container->get(DefaultCommand::class)->getName(), true);
$app->run();
