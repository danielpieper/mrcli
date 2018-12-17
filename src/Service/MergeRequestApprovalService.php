<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;

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
        return MergeRequestApproval::fromArray($mergeRequestApproval);
    }
}
