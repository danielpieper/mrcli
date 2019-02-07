<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests;

use DanielPieper\MergeReminder\Command\ApproverCommand;
use DanielPieper\MergeReminder\Command\OverviewCommand;
use DanielPieper\MergeReminder\Command\ProjectCommand;
use DanielPieper\MergeReminder\ServiceProvider\AppServiceProvider;
use DanielPieper\MergeReminder\ServiceProvider\CommandServiceProvider;
use DanielPieper\MergeReminder\ServiceProvider\ConfigurationServiceProvider;
use DanielPieper\MergeReminder\ServiceProvider\GitlabServiceProvider;
use DanielPieper\MergeReminder\ServiceProvider\SlackServiceProvider;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Http\Mock\Client;
use League\Container\Container;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ApplicationTestCase extends TestCase
{
    /** @var Application */
    public $application;

    /** @var Container */
    public $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->container->share(OutputInterface::class, ConsoleOutput::class);
        $this->container->share(LoggerInterface::class, ConsoleLogger::class)
            ->addArgument(OutputInterface::class);

        // Mock http requests
        $this->container->share(HttpClient::class, Client::class);
        // Disable caching
        $this->container->share(CacheItemPoolInterface::class, NullAdapter::class);

        $this->container
            ->addServiceProvider(ConfigurationServiceProvider::class)
            ->addServiceProvider(GitlabServiceProvider::class)
            ->addServiceProvider(SlackServiceProvider::class)
            ->addServiceProvider(CommandServiceProvider::class)
            ->addServiceProvider(AppServiceProvider::class);


        $this->application = new Application('mrcli', '1.0.0');
        $this->application->addCommands([
            $this->container->get(OverviewCommand::class),
            $this->container->get(ProjectCommand::class),
            $this->container->get(ApproverCommand::class),
        ]);
    }

    protected function createResponse(string $filename): Response
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/' . $filename);
        return new Response(
            200,
            ['Content-type' => 'application/json'],
            $fixture
        );
    }
}
