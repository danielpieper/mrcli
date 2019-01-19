<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

use Carbon\Carbon;

class MergeRequestApproval
{
    public const MERGE_STATUS_CANNOT_BE_MERGED = 'cannot_be_merged';

    /** @var string */
    private $mergeStatus;

    /** @var int */
    private $approvalsRequired;

    /** @var int */
    private $approvalsLeft;

    /** @var User[] */
    private $approvedBy;

    /** @var User[] */
    private $approvers;

    /** @var Group[] */
    private $approverGroups;

    /** @var Carbon */
    private $updatedAt;

    /** @var Carbon */
    private $createdAt;

    /** @var MergeRequest */
    private $mergeRequest;


    /**
     * Project constructor.
     * @param string $mergeStatus
     * @param int $approvalsRequired
     * @param int $approvalsLeft
     * @param array $approvedBy
     * @param array $approvers
     * @param array $approverGroups
     * @param Carbon $updatedAt
     * @param Carbon $createdAt
     * @param MergeRequest $mergeRequest
     */
    public function __construct(
        string $mergeStatus,
        int $approvalsRequired,
        int $approvalsLeft,
        array $approvedBy,
        array $approvers,
        array $approverGroups,
        Carbon $updatedAt,
        Carbon $createdAt,
        MergeRequest $mergeRequest
    ) {
        $this->mergeStatus = $mergeStatus;
        $this->approvalsRequired = $approvalsRequired;
        $this->approvalsLeft = $approvalsLeft;
        $this->approvedBy = $approvedBy;
        $this->approvers = $approvers;
        $this->approverGroups = $approverGroups;
        $this->updatedAt = $updatedAt;
        $this->createdAt = $createdAt;
        $this->mergeRequest = $mergeRequest;
    }

    /**
     * @param array $mergeRequestApproval
     * @return MergeRequestApproval
     * @throws \Exception
     */
    public static function fromArray(array $mergeRequestApproval): self
    {
        return new self(
            (string)$mergeRequestApproval['merge_status'],
            (int)$mergeRequestApproval['approvals_required'],
            (int)$mergeRequestApproval['approvals_left'],
            $mergeRequestApproval['approved_by'],
            $mergeRequestApproval['approvers'],
            $mergeRequestApproval['approver_groups'],
            new Carbon($mergeRequestApproval['updated_at']),
            new Carbon($mergeRequestApproval['created_at']),
            $mergeRequestApproval['merge_request']
        );
    }

    /**
     * @return string
     */
    public function getMergeStatus(): string
    {
        return $this->mergeStatus;
    }

    /**
     * @return int
     */
    public function getApprovalsRequired(): int
    {
        return $this->approvalsRequired;
    }

    /**
     * @return int
     */
    public function getApprovalsLeft(): int
    {
        return $this->approvalsLeft;
    }

    /**
     * @return User[]
     */
    public function getApprovedBy(): array
    {
        return $this->approvedBy;
    }

    /**
     * @return User[]
     */
    public function getApprovers(): array
    {
        return $this->approvers;
    }

    /**
     * @return array
     */
    public function getApproverNames(): array
    {
        $result = [];
        foreach ($this->approvers as $approver) {
            $result[] = $approver->getUsername();
        }

        return $result;
    }

    /**
     * @return Group[]
     */
    public function getApproverGroups(): array
    {
        return $this->approverGroups;
    }

    /**
     * @return array
     */
    public function getApproverGroupNames(): array
    {
        $result = [];
        foreach ($this->approverGroups as $approverGroup) {
            $result[] = $approverGroup->getName();
        }

        return $result;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @return MergeRequest
     */
    public function getMergeRequest(): MergeRequest
    {
        return $this->mergeRequest;
    }
}
