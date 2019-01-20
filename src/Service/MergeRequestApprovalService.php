<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;
use DanielPieper\MergeReminder\ValueObject\Group;

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
     * @return MergeRequestApproval|null
     * @throws \Exception
     */
    public function find(MergeRequest $mergeRequest): ?MergeRequestApproval
    {
        $mergeRequestApproval = $this->gitlabClient->mergeRequests()->approvals(
            $mergeRequest->getProject()->getId(),
            $mergeRequest->getIid()
        );
        if (!is_array($mergeRequestApproval)) {
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


        $this->transformApprovers($mergeRequestApproval['approved_by']);
        $this->transformApprovers($mergeRequestApproval['approvers']);
        $this->transformApprovers($mergeRequestApproval['suggested_approvers']);

        $approverIdList = array_map(function (User $approver) {
            return $approver->getId();
        }, $mergeRequestApproval['approvers']);
        $mergeRequestApproval['suggested_approvers'] = array_filter(
            $mergeRequestApproval['suggested_approvers'],
            function (User $suggestedApprover) use ($approverIdList) {
                return !in_array($suggestedApprover->getId(), $approverIdList);
            }
        );

        array_walk($mergeRequestApproval['approver_groups'], function (&$approverGroup) {
            $approverGroup = Group::fromArray($approverGroup['group']);
        });

        return MergeRequestApproval::fromArray($mergeRequestApproval);
    }

    /**
     * @param array $approvers
     * @return void
     */
    private function transformApprovers(array &$approvers): void
    {
        array_walk($approvers, function (&$approver) {
            // suggested_approvers are not encapsulated in 'user'...
            if (isset($approver['user'])) {
                $approver = $approver['user'];
            }
            $approver = User::fromArray($approver);
        });
    }
}
