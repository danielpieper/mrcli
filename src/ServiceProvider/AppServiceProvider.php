<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use DanielPieper\MergeReminder\Command\DefaultCommand;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Service\SlackService;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        MergeRequestService::class,
        ProjectService::class,
        SlackService::class,
        DefaultCommand::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->add(MergeRequestService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->add(ProjectService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->add(SlackService::class)
            ->addArgument(\Razorpay\Slack\Client::class);

        $container->add(DefaultCommand::class)
            ->addArgument(ProjectService::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(SlackService::class);
    }
}
