<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ServiceProvider;

use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class ConfigurationServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $provides = [
        'GITLAB_URL',
        'GITLAB_TOKEN',
        'SLACK_WEBHOOK_URL',
        'SLACK_CHANNEL',
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
        $dotEnv = \Dotenv\Dotenv::create(__DIR__ . '../..');
        $dotEnv->safeLoad();
        $dotEnv->required(['GITLAB_TOKEN'])->notEmpty();

        /** @var Container $container */
        $container = $this->getContainer();

        $container->add('SLACK_WEBHOOK_URL', getenv('SLACK_WEBHOOK_URL'));
        $container->add('SLACK_CHANNEL', getenv('SLACK_CHANNEL'));
        $container->add('GITLAB_TOKEN', getenv('GITLAB_TOKEN'));
        $container->add('GITLAB_URL', (getenv('GITLAB_URL') ? getenv('GITLAB_URL') : 'https://gitlab.com'));
    }
}
