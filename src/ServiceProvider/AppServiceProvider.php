<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Service\UserService;
use Gitlab\Client;
use Gitlab\ResultPager;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;

class AppServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        ResultPager::class,
        MergeRequestApprovalService::class,
        ProjectService::class,
        MergeRequestService::class,
        UserService::class,
        MergeRequestApprovalFilter::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->share(ResultPager::class)
            ->addArgument(Client::class);

        $container->share(MergeRequestApprovalService::class)
            ->addArgument(Client::class);

        $container->share(ProjectService::class)
            ->addArguments([
                Client::class,
                ResultPager::class,
            ]);

        $container->share(MergeRequestService::class)
            ->addArguments([
                Client::class,
                ResultPager::class,
            ]);

        $container->share(UserService::class)
            ->addArgument(Client::class);

        $container->share(MergeRequestApprovalFilter::class);
    }
}
