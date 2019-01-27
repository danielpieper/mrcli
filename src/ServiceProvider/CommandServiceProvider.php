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

class CommandServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
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

        $container->share(OverviewCommand::class)
            ->addArguments([
                ProjectService::class,
                MergeRequestService::class,
                MergeRequestApprovalService::class,
            ]);

        $container->share(ProjectCommand::class)
            ->addArguments([
                ProjectService::class,
                MergeRequestService::class,
                MergeRequestApprovalService::class,
                MergeRequestApprovalFilter::class,
            ]);

        $container->share(ApproverCommand::class)
            ->addArguments([
                ProjectService::class,
                UserService::class,
                MergeRequestService::class,
                MergeRequestApprovalService::class,
                MergeRequestApprovalFilter::class,
            ]);
    }
}
