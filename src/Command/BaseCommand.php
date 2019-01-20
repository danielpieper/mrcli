<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    /**
     * @param OutputInterface $output
     * @param MergeRequestApproval $mergeRequestApproval
     */
    protected function printMergeRequestApproval(
        OutputInterface $output,
        MergeRequestApproval $mergeRequestApproval
    ): void {
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

        $suggestedApproverNames = $mergeRequestApproval->getSuggestedApproverNames();
        if (count($suggestedApproverNames) > 0) {
            $rows[] = [
                'Suggested approvers:',
                implode(', ', $suggestedApproverNames),
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
