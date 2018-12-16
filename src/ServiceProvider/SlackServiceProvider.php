<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Razorpay\Slack\Client;

class SlackServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        Client::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->add(Client::class)
            ->addArgument('SLACK_WEBHOOK_URL')
            ->addArgument([
                'username' => 'Friendly Merge Reminder',
                'icon' => ':owl:',
                'channel' => '#gitlab',
            ]);
    }
}
