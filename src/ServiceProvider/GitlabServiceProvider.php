<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use Gitlab\Client;
use Gitlab\HttpClient\Builder;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Discovery\StreamFactoryDiscovery;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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

        $container->share(CacheItemPoolInterface::class, FilesystemAdapter::class)
            ->addArguments([
                'namespace' => 'mrcli',
                'defaultLifetime' => 0,
                'directory' => null,
            ]);

        $container->share(CachePlugin::class)
            ->addArgument(CacheItemPoolInterface::class)
            ->addArgument(StreamFactoryDiscovery::find())
            ->addArgument([
                'default_ttl' => 600,
                'respect_response_cache_directives' => [],
            ]);

        $container->share(Builder::class)
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
