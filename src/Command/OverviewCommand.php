<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OverviewCommand extends BaseCommand
{
    /** @var MergeRequestService */
    private $mergeRequestService;

    /** @var MergeRequestApprovalService */
    private $mergeRequestApprovalService;

    public function __construct(
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService
    ) {
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('overview')
            ->setAliases(['o'])
            ->setDescription('Get an overview about all pending merge requests');
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
        /** @var MergeRequest[] $mergeRequests */
        $mergeRequests = $this->mergeRequestService->all();
        if (count($mergeRequests) == 0) {
            $output->writeln('No pending merge requests.');
            return;
        }

        $mergeRequestApprovals = $this->getMergeRequestApprovals($mergeRequests);
        if (count($mergeRequestApprovals) == 0) {
            $output->writeln('No pending merge request approvals.');
            return;
        }

        $headers = $this->getHeaders($mergeRequestApprovals);
        $rows = $this->getRows($headers, $mergeRequestApprovals);

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }

    /**
     * @param array $mergeRequestApprovals
     * @return array
     */
    private function getHeaders(array $mergeRequestApprovals): array
    {
        $headers = array_unique(array_map(function (MergeRequestApproval $mergeRequestApproval) {
            return $mergeRequestApproval->getMergeRequest()->getProject()->getName();
        }, $mergeRequestApprovals));
        array_unshift($headers, 'Total');
        array_unshift($headers, 'Approver');

        return $headers;
    }

    /**
     * @param array $headers
     * @param array $mergeRequestApprovals
     * @return array
     */
    private function getRows(array $headers, array $mergeRequestApprovals): array
    {
        $headerIndices = array_flip($headers);
        $rows = array_reduce(
            $mergeRequestApprovals,
            function ($carry, MergeRequestApproval $mergeRequestApproval) use ($headerIndices) {
                $approverNames = $mergeRequestApproval->getApproverNames();
                foreach ($approverNames as $approverName) {
                    if (!isset($carry[$approverName])) {
                        $carry[$approverName] = array_fill(0, count($headerIndices) - 1, 0);
                        array_unshift($carry[$approverName], $approverName);
                    }

                    $project = $mergeRequestApproval->getMergeRequest()->getProject()->getName();
                    $index = $headerIndices[$project];
                    $carry[$approverName][$index]++;
                    $carry[$approverName][1]++;
                }
                return $carry;
            },
            []
        );

        usort($rows, function ($approverA, $approverB) {
            if ($approverA[1] == $approverB[1]) {
                return 0;
            }
            return ($approverA[1] > $approverB[1] ? -1 : 1);
        });

        return array_values($rows);
    }
}
