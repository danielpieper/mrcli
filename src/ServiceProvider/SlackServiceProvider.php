<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\SlackServiceAwareInterface;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Razorpay\Slack\Client;

class SlackServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        Client::class,
        SlackService::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
    }

    /**
     * Method will be invoked on registration of a service provider implementing
     * this interface. Provides ability for eager loading of Service Providers.
     *
     * @return void
     */
    public function boot()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        if (!$container->get('SLACK_WEBHOOK_URL') || !$container->get('SLACK_CHANNEL')) {
            return;
        }

        $container->share(Client::class)
            ->addArgument('SLACK_WEBHOOK_URL')
            ->addArgument([
                'username' => 'Friendly Merge Reminder',
                'icon' => ':owl:',
                'channel' => $container->get('SLACK_CHANNEL'),
                'allow_markdown' => true,
            ]);

        $container->share(SlackService::class)
            ->addArgument(Client::class);

        $container->inflector(SlackServiceAwareInterface::class)
            ->invokeMethod('setSlackService', [SlackService::class]);
    }
}
