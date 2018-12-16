<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{
    /** @var ProjectService */
    private $projectService;

    /** @var MergeRequestService */
    private $mergeRequestService;

    public function __construct(ProjectService $projectService, MergeRequestService $mergeRequestService)
    {
        $this->projectService = $projectService;
        $this->mergeRequestService = $mergeRequestService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('merge-requests')
            ->setDescription('List pending merge requests')
            ->addArgument(
                'projects',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Gitlab project id\'s (separate by space)'
            );
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
        $projects = [];
        foreach ($input->getArgument('projects') as $projectId) {
            $projects[] = $this->projectService->get((int)$projectId);
        }

        /** @var MergeRequest[] $mergeRequests */
        $mergeRequests = [];
        foreach ($projects as $project) {
            $mergeRequests = array_merge($mergeRequests, $this->mergeRequestService->all($project));
        }

        $rows = [];
        foreach ($mergeRequests as $mergeRequest) {
            $rows[] = [
                $mergeRequest->getProject()->getName(),
                $mergeRequest->getTitle(),
                $mergeRequest->getAuthor()->getUsername(),
                $mergeRequest->getAssignee()->getUsername(),
            ];
        }

        if (count($rows) == 0) {
            $output->writeln('No results.');
            return;
        }

         $table = new Table($output);
         $table
             ->setHeaderTitle('Merge requests')
             ->setHeaders(['Project', 'Title', 'Author', 'Assignee'])
             ->setRows($rows) ;
        $table->render();
    }
}
