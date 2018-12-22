<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;

class MergeRequestApprovalService
{
    /** @var \Gitlab\Client */
    private $gitlabClient;

    public function __construct(\Gitlab\Client $gitlabClient)
    {
        $this->gitlabClient = $gitlabClient;
    }

    /**
     * @param MergeRequest $mergeRequest
     * @return MergeRequestApproval
     * @throws \Exception
     */
    public function find(MergeRequest $mergeRequest): MergeRequestApproval
    {
        $mergeRequestApproval = $this->gitlabClient->mergeRequests()->approvals(
            $mergeRequest->getProject()->getId(),
            $mergeRequest->getIid()
        );
        if (!$mergeRequestApproval) {
            return null;
        }
        return $this->transform($mergeRequest, $mergeRequestApproval);
    }

    /**
     * @param MergeRequest $mergeRequest
     * @return MergeRequestApproval
     * @throws MergeRequestApprovalNotFoundException
     * @throws \Exception
     */
    public function get(MergeRequest $mergeRequest): MergeRequestApproval
    {
        $mergeRequestApproval = $this->find($mergeRequest);
        if (!$mergeRequestApproval) {
            throw new MergeRequestApprovalNotFoundException();
        }
        return $mergeRequestApproval;
    }

    /**
     * @param MergeRequest $mergeRequest
     * @param array $mergeRequestApproval
     * @return MergeRequestApproval
     * @throws \Exception
     */
    private function transform(MergeRequest $mergeRequest, array $mergeRequestApproval): MergeRequestApproval
    {
        $mergeRequestApproval['merge_request'] = $mergeRequest;

        $approvers = [];
        foreach ($mergeRequestApproval['approvers'] as $approver) {
            if (is_array($approver) && isset($approver['user'])) {
                $approvers[] = User::fromArray($approver['user']);
            }
        }
        $mergeRequestApproval['approvers'] = $approvers;

        $approvedBy = [];
        foreach ($mergeRequestApproval['approved_by'] as $approver) {
            if (is_array($approver) && isset($approver['user'])) {
                $approvedBy[] = User::fromArray($approver['user']);
            }
        }
        $mergeRequestApproval['approved_by'] = $approvers;

        return MergeRequestApproval::fromArray($mergeRequestApproval);
    }
}
