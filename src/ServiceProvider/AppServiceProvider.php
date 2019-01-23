<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use DanielPieper\MergeReminder\Command\ApproverCommand;
use DanielPieper\MergeReminder\Command\OverviewCommand;
use DanielPieper\MergeReminder\Command\ProjectCommand;
use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
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
        UserService::class,
        OverviewCommand::class,
        ProjectCommand::class,
        ApproverCommand::class,
        MergeRequestApprovalFilter::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->share(MergeRequestApprovalService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->share(ProjectService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->share(MergeRequestService::class)
            ->addArgument(\Gitlab\Client::class)
            ->addArgument(ProjectService::class);

        $container->share(UserService::class)
            ->addArgument(\Gitlab\Client::class);

        $container->share(MergeRequestApprovalFilter::class);

        $container->share(OverviewCommand::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class);

        $container->share(ProjectCommand::class)
            ->addArgument(ProjectService::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class)
            ->addArgument(MergeRequestApprovalFilter::class);

        $container->share(ApproverCommand::class)
            ->addArgument(UserService::class)
            ->addArgument(MergeRequestService::class)
            ->addArgument(MergeRequestApprovalService::class)
            ->addArgument(MergeRequestApprovalFilter::class);
    }
}
