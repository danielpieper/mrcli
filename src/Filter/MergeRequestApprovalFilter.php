<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Filter;

use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;

class MergeRequestApprovalFilter
{
    /** @var User */
    private $user;

    /** @var bool */
    private $includeSuggestedApprovers;

    /**
     * MergeRequestApprovalFilter constructor.
     * @param User|null $user
     * @param bool $includeSuggestedApprovers
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct(?User $user = null, bool $includeSuggestedApprovers = false)
    {
        $this->user = $user;
        $this->includeSuggestedApprovers = $user && $includeSuggestedApprovers;
    }

    /**
     * @param User $user
     * @param bool $includeSuggestedApprovers
     * @return MergeRequestApprovalFilter
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function addUser(User $user, bool $includeSuggestedApprovers = false): MergeRequestApprovalFilter
    {
        return new static($user, $includeSuggestedApprovers);
    }

    /**
     * @param bool $includeSuggestedApprovers
     * @return MergeRequestApprovalFilter
     */
    public function addIncludeSuggestedApprovers(bool $includeSuggestedApprovers): MergeRequestApprovalFilter
    {
        return new static($this->user, $includeSuggestedApprovers);
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return bool
     */
    public function __invoke(MergeRequestApproval $mergeRequestApproval): bool
    {
        $hasApprovalsLeft = $mergeRequestApproval->getApprovalsLeft() > 0;
        $isWorkInProgress = $mergeRequestApproval->getMergeRequest()->isWorkInProgress();

        if ($this->user) {
            return $hasApprovalsLeft && !$isWorkInProgress && $this->isUserInvolved($mergeRequestApproval);
        }

        return $hasApprovalsLeft && !$isWorkInProgress;
    }

    public function isUserInvolved(MergeRequestApproval $mergeRequestApproval): bool
    {
        $isApprover = $this->isApprover($mergeRequestApproval);
        $isAssignee = $this->isAssignee($mergeRequestApproval);
        $isSuggestedApprover = $this->includeSuggestedApprovers && $this->isSuggestedApprover($mergeRequestApproval);

        return $isApprover || $isAssignee || $isSuggestedApprover;
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return bool
     */
    public function isSuggestedApprover(MergeRequestApproval $mergeRequestApproval): bool
    {
        if (!$this->user) {
            return false;
        }
        return in_array($this->user->getUsername(), $mergeRequestApproval->getSuggestedApproverNames());
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return bool
     */
    public function isApprover(MergeRequestApproval $mergeRequestApproval): bool
    {
        if (!$this->user) {
            return false;
        }
        return in_array($this->user->getUsername(), $mergeRequestApproval->getApproverNames());
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return bool
     */
    public function isAssignee(MergeRequestApproval $mergeRequestApproval): bool
    {
        if (!$this->user || !$mergeRequestApproval->getMergeRequest()->getAssignee()) {
            return false;
        }
        return $mergeRequestApproval->getMergeRequest()->getAssignee()->getUsername() == $this->user->getUsername();
    }
}
