<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use Gitlab\Client;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;

class GitlabServiceProvider extends AbstractServiceProvider
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
            ->addMethodCall('setUrl', ['GITLAB_URL'])
            ->addMethodCall('authenticate', [
                'GITLAB_TOKEN',
                Client::AUTH_URL_TOKEN,
            ]);
    }
}
