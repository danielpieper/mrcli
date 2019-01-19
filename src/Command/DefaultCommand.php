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
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
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
        $mergeRequestApprovals = array_filter($mergeRequestApprovals, function (MergeRequestApproval $item) {
            return $item->getApprovalsLeft() > 0 && !$item->getMergeRequest()->isWorkInProgress();
        });
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

        if ($input->getOption('print')) {
            foreach ($mergeRequestApprovals as $mergeRequestApproval) {
                $this->printMergeRequestApproval($output, $mergeRequestApproval);
            }
            return;
        }

        $this->slackService->postMessage($mergeRequestApprovals);
    }

    /**
     * @param OutputInterface $output
     * @param MergeRequestApproval $mergeRequestApproval
     */
    private function printMergeRequestApproval(OutputInterface $output, MergeRequestApproval $mergeRequestApproval): void
    {
        $mergeRequest = $mergeRequestApproval->getMergeRequest();
        $output->writeln($mergeRequest->getAuthor()->getUsername());
        $output->writeln(sprintf(
            '[%s] <fg=%s>%s</>',
            $mergeRequest->getProject()->getName(),
            $this->getColor($mergeRequestApproval),
            $mergeRequest->getTitle()
        ));
        $output->writeln($mergeRequest->getWebUrl());
        if ($output->isVerbose()) {
            $output->writeln($mergeRequest->getDescription());
        }

        $rows = [
            [
                'Created:',
                $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans()
            ],
        ];


        if ($mergeRequestApproval->getUpdatedAt()->diffInDays($mergeRequestApproval->getCreatedAt()) > 0) {
            $rows[] = [
                'Updated:',
                $mergeRequestApproval->getUpdatedAt()->shortRelativeToNowDiffForHumans(),
            ];
        }

        $approverNames = $mergeRequestApproval->getApproverNames();
        if (count($approverNames) > 0) {
            $rows[] = [
                'Approvers:',
                implode(', ', $approverNames),
            ];
        }

        $approverGroupNames = $mergeRequestApproval->getApproverGroupNames();
        if (count($approverGroupNames) > 0) {
            $rows[] = [
                'Approver Groups:',
                implode(', ', $approverGroupNames),
            ];
        }

        $table = new Table($output);
        $table->setStyle('compact');
        $table->setRows($rows);
        $table->render();

        $output->writeln('');
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return string
     */
    private function getColor(MergeRequestApproval $mergeRequestApproval): string
    {
        $ageInDays = $mergeRequestApproval->getCreatedAt()->diffInDays();

        if ($ageInDays > 2) {
            return 'red';
        }
        if ($ageInDays > 1) {
            return 'yellow';
        }
        return 'green';
    }
}
