<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use Gitlab\Client;
use Gitlab\HttpClient\Builder;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\HttpClient;
use Http\Discovery\StreamFactoryDiscovery;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class GitlabServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        LoggerPlugin::class,
        CachePlugin::class,
        Builder::class,
        Client::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->share(LoggerPlugin::class)
            ->addArgument(LoggerInterface::class);

        $container->share(CachePlugin::class)
            ->addArguments([
                CacheItemPoolInterface::class,
                StreamFactoryDiscovery::find(),
                [
                    'default_ttl' => 600,
                    'respect_response_cache_directives' => [],
                ]
            ]);

        $container->share(Builder::class)
            ->addArgument(HttpClient::class)
            ->addMethodCall('addPlugin', [LoggerPlugin::class])
            ->addMethodCall('addPlugin', [CachePlugin::class]);

        $container->share(Client::class)
            ->addArgument(Builder::class)
            ->addMethodCall('setUrl', ['GITLAB_URL'])
            ->addMethodCall('authenticate', [
                'GITLAB_TOKEN',
                Client::AUTH_URL_TOKEN,
            ]);
    }
}
