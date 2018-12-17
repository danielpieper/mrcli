<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Transformer;

use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;

class MergeRequestApprovalTransformer
{
    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return string
     */
    public function transform(MergeRequestApproval $mergeRequestApproval): string
    {
        $mergeRequest = $mergeRequestApproval->getMergeRequest();
        $project = $mergeRequest->getProject();

        return implode("\n", [
            strtr('\[:project\] [:title](:url)', [
                ':project' => $project->getName(),
                ':title' => $mergeRequest->getTitle(),
                ':url' => $mergeRequest->getWebUrl(),
            ]),
            strtr(':created_at - Waiting on :approvers', [
                ':created_at' => $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans(),
                ':approvers' => $this->getApprovers($mergeRequestApproval)
            ]),
        ]);
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return string
     */
    private function getApprovers(MergeRequestApproval $mergeRequestApproval): string
    {
        /** @var User[] $approvers */
        $approvers = $mergeRequestApproval->getApprovers();

        $result = [];
        foreach ($approvers as $approver) {
            $result[] = $approver->getUsername();
        }

        return implode(' ', $result);
    }
}
