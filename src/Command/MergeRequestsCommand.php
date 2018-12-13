<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use Symfony\Component\Console\Helper\Table;
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

        $projectService = new ProjectService($client);
        $mergeRequestService = new MergeRequestService($client);

        $projects = [];
        foreach ($config['gitlab_projects'] as $gitlabProjectId) {
            $projects[] = $projectService->get($gitlabProjectId);
        }

        $mergeRequests = [];
        foreach ($projects as $project) {
            $mergeRequests = array_merge($mergeRequests, $mergeRequestService->all($project));
        }

        $rows = [];
        foreach ($mergeRequests as $mergeRequest) {
            $rows[] = [
                $mergeRequest->project()->name(),
                $mergeRequest->title(),
            ];
        }

        if (count($rows) == 0) {
            $output->writeln('No results.');
            return;
        }

         $table = new Table($output);
         $table
            ->setHeaders(['Project', 'Name'])
            ->setRows($rows) ;
        $table->render();
    }
}
