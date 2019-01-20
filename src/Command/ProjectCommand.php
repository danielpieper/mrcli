<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectCommand extends BaseCommand
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
            ->setName('project')
            ->setAliases(['p'])
            ->setDescription('Get a project\'s pending merge-requests')
            ->addArgument(
                'project_ids',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Gitlab project id\'s (separate by space)'
            )
            ->addOption(
                'slack',
                's',
                InputOption::VALUE_NONE,
                'Post to slack channel'
            );
    }

    /**
     * @param array $idList
     * @return array
     * @throws \DanielPieper\MergeReminder\Exception\ProjectNotFoundException
     */
    private function getProjects(array $idList): array
    {
        $projects = [];
        foreach ($idList as $id) {
            $projects[] = $this->projectService->get((int)$id);
        }
        return $projects;
    }

    /**
     * @param array $projects
     * @return array
     * @throws \Exception
     */
    private function getMergeRequests(array $projects): array
    {
        $mergeRequests = [];
        foreach ($projects as $project) {
            $mergeRequests = array_merge($mergeRequests, $this->mergeRequestService->allByProject($project));
        }
        return $mergeRequests;
    }

    /**
     * @param array $mergeRequests
     * @return array
     * @throws MergeRequestApprovalNotFoundException
     */
    private function getMergeRequestApprovals(array $mergeRequests): array
    {
        $mergeRequestApprovals = [];
        foreach ($mergeRequests as $mergeRequest) {
            $mergeRequestApprovals[] = $this->mergeRequestApprovalService->get($mergeRequest);
        }
        return array_filter($mergeRequestApprovals, function (MergeRequestApproval $item) {
            $hasApprovalsLeft = $item->getApprovalsLeft() > 0;
            $isWorkInProgress = $item->getMergeRequest()->isWorkInProgress();

            return $hasApprovalsLeft && !$isWorkInProgress;
        });
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
        $projects = $this->getProjects($input->getArgument('project_ids'));

        /** @var MergeRequest[] $mergeRequests */
        $mergeRequests = $this->getMergeRequests($projects);
        if (count($mergeRequests) == 0) {
            $output->writeln('No pending merge requests.');
            return;
        }

        $mergeRequestApprovals = $this->getMergeRequestApprovals($mergeRequests);
        if (count($mergeRequestApprovals) == 0) {
            $output->writeln('No pending merge request approvals.');
            return;
        }

        usort($mergeRequestApprovals, function (MergeRequestApproval $approvalA, MergeRequestApproval $approvalB) {
            if ($approvalA->getCreatedAt()->equalTo($approvalB->getCreatedAt())) {
                return 0;
            }
            return ($approvalA->getCreatedAt()->lessThan($approvalB->getCreatedAt()) ? -1 : 1);
        });

        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $this->printMergeRequestApproval($output, $mergeRequestApproval);
        }

        if ($input->getOption('slack')) {
            $this->slackService->postMessage($mergeRequestApprovals);
        }
    }
}
