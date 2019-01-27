<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use Gitlab\Client;
use Gitlab\HttpClient\Builder;
use Gitlab\ResultPager;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Discovery\StreamFactoryDiscovery;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class GitlabServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        OutputInterface::class,
        LoggerInterface::class,
        LoggerPlugin::class,
        CacheItemPoolInterface::class,
        CachePlugin::class,
        Builder::class,
        Client::class,
        ResultPager::class,
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

        $container->share(OutputInterface::class, ConsoleOutput::class);

        $container->share(LoggerInterface::class, ConsoleLogger::class)
            ->addArgument(OutputInterface::class);

        $container->inflector(LoggerAwareInterface::class)
            ->invokeMethod('setLogger', [LoggerInterface::class]);

        $container->share(LoggerPlugin::class)
            ->addArgument(LoggerInterface::class);

        $container->share(CacheItemPoolInterface::class, FilesystemAdapter::class)
            ->addArguments([
                'namespace' => 'mrcli',
                'defaultLifetime' => 0,
                'directory' => null,
            ]);

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
            ->addMethodCall('addPlugin', [LoggerPlugin::class])
            ->addMethodCall('addPlugin', [CachePlugin::class]);

        $container->share(Client::class)
            ->addArgument(Builder::class)
            ->addMethodCall('setUrl', ['GITLAB_URL'])
            ->addMethodCall('authenticate', [
                'GITLAB_TOKEN',
                Client::AUTH_URL_TOKEN,
            ]);

        $container->share(ResultPager::class)
            ->addArgument(Client::class);
    }
}
