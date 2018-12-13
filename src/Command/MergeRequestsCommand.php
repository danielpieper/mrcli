<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeRequestsCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('merge-requests')
            ->setDescription('List pending merge requests');
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
        $config = $this->getConfiguration();

        $client = \Gitlab\Client::create($config['gitlab_url'])->authenticate(
            $config['gitlab_token'],
            \Gitlab\Client::AUTH_URL_TOKEN
        );

        $projects = $client->projects()->all([
            'with_merge_requests_enabled' => true,
            'membership' => true,
        ]);

        foreach ($projects as $project) {
            $output->writeln($project['id'] . ' ' . $project['name']);
        }
        $mergeRequests = $client->mergeRequests()->all(9102042);
        var_dump($mergeRequests);

//                if (!$output->isQuiet()) {
//                    $output->writeln(sprintf('<info>file %s saved.</info>', $filename));
//                }
    }
}
