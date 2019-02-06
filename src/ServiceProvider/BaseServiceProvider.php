<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use Http\Adapter\Guzzle6\Client;
use Http\Client\HttpClient;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class BaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        OutputInterface::class,
        LoggerInterface::class,
        CacheItemPoolInterface::class,
        HttpClient::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container->share(OutputInterface::class, ConsoleOutput::class);

        $container->share(LoggerInterface::class, ConsoleLogger::class)
            ->addArgument(OutputInterface::class);

        $container->inflector(LoggerAwareInterface::class)
            ->invokeMethod('setLogger', [LoggerInterface::class]);

        $container->share(CacheItemPoolInterface::class, FilesystemAdapter::class)
            ->addArguments([
                'namespace' => 'mrcli',
                'defaultLifetime' => 0,
                'directory' => null,
            ]);

        $container->share(HttpClient::class, Client::class);
    }
}
