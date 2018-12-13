<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Dump example configuration file');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = <<<EOF
gitlab_url: https://gitlab.com
gitlab_token: YOUR GITLAB TOKEN
slack_webhook_url: YOUR SLACK WEBHOOK URL
EOF
        ;
        $output->writeln($config);
    }
}
