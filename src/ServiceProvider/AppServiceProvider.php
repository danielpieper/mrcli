<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use DanielPieper\MergeReminder\Command\ApproverCommand;
use DanielPieper\MergeReminder\Command\OverviewCommand;
use DanielPieper\MergeReminder\Command\ProjectCommand;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\Service\UserService;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        MergeRequestService::class,
        MergeRequestApprovalService::class,
        ProjectService::class,
        SlackService::class,
        OverviewCommand::class,
        ProjectCommand::class,
        ApproverCommand::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->add(MergeRequestApprovalService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->add(ProjectService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->add(MergeRequestService::class)
            ->addArgument(\Gitlab\Client::class)
            ->addArgument(ProjectService::class);

        $container->add(UserService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->add(SlackService::class)
            ->addArgument(\Razorpay\Slack\Client::class);

        $container->add(OverviewCommand::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class);

        $container->add(ProjectCommand::class)
            ->addArgument(ProjectService::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class)
            ->addArgument(SlackService::class);

        $container->add(ApproverCommand::class)
            ->addArgument(UserService::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class)
            ->addArgument(SlackService::class);
    }
}
