#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use DanielPieper\MergeReminder\Command\ConfigureCommand;
use DanielPieper\MergeReminder\Command\DefaultCommand;
use DanielPieper\MergeReminder\Command\SlackCommand;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use Symfony\Component\Console\Application;

$dotEnv = new \Dotenv\Dotenv(__DIR__);
$dotEnv->load();
$dotEnv->required(['GITLAB_TOKEN'])->notEmpty();

$app = new Application('mrcli', '@package_version@');

$container = new \League\Container\Container();
$container->add(\Gitlab\Client::class, function () {
    $gitlabUrl = getenv('GITLAB_URL');
    if (!$gitlabUrl) {
        $gitlabUrl = 'https://gitlab.com';
    }
    return \Gitlab\Client::create($gitlabUrl)->authenticate(
        getenv('GITLAB_TOKEN'),
        \Gitlab\Client::AUTH_URL_TOKEN
    );
});

$container->add(MergeRequestService::class)
    ->addArgument(\Gitlab\Client::class);

$container->add(ProjectService::class)
    ->addArgument(\Gitlab\Client::class);

$container->add(DefaultCommand::class)
    ->addArgument(ProjectService::class)
    ->addArgument(MergeRequestService::class);


$app->add($container->get(DefaultCommand::class));
$app->setDefaultCommand($container->get(DefaultCommand::class)->getName(), true);
$app->run();
