<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SlackCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('slack')
            ->setDescription('Notify users about pending merge requests via slack channel');
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
    }
}
