<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{
    /** @var ProjectService */
    private $projectService;

    /** @var MergeRequestService */
    private $mergeRequestService;

    /** @var MergeRequestApprovalService */
    private $mergeRequestApprovalService;

    /** @var SlackService */
    private $slackService;

    public function __construct(
        ProjectService $projectService,
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService,
        SlackService $slackService
    ) {
        $this->projectService = $projectService;
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
        $this->slackService = $slackService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('merge-requests')
            ->setDescription('Post pending merge-requests to a slack channel')
            ->addArgument(
                'project_ids',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Gitlab project id\'s (separate by space)'
            )
            ->addOption(
                'print',
                'p',
                InputOption::VALUE_NONE,
                'Print pending merge requests and exit.'
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
        foreach ($input->getArgument('project_ids') as $projectId) {
            $projects[] = $this->projectService->get((int)$projectId);
        }

        /** @var MergeRequest[] $mergeRequests */
        $mergeRequests = [];
        foreach ($projects as $project) {
            $mergeRequests = array_merge($mergeRequests, $this->mergeRequestService->all($project));
        }
        if (count($mergeRequests) == 0) {
            $output->writeln('No pending merge requests.');
            return;
        }

        $mergeRequestApprovals = [];
        foreach ($mergeRequests as $mergeRequest) {
            $mergeRequestApprovals[] = $this->mergeRequestApprovalService->get($mergeRequest);
        }

        if ($input->getOption('print')) {
            $this->print($output, $mergeRequestApprovals);
            return;
        }

        $this->slackService->postMessage($mergeRequestApprovals);
    }

    private function print(OutputInterface $output, array $mergeRequestApprovals): void
    {
        $rows = [];
        /** @var MergeRequestApproval $mergeRequestApproval */
        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $mergeRequest = $mergeRequestApproval->getMergeRequest();
            $rows[] = [
                $mergeRequest->getProject()->getName(),
                $mergeRequest->getTitle(),
                $mergeRequest->getAuthor()->getUsername(),
                $mergeRequest->getAssignee()->getUsername(),
                $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans(),
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaderTitle('Merge requests')
            ->setHeaders(['Project', 'Title', 'Author', 'Assignee', 'Created'])
            ->setRows($rows);
        $table->render();
    }
}
