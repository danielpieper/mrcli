<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Filter;

use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Tests\TestCase;

class MergeRequestApprovalFilterTest extends TestCase
{
    public function invokeProvider(): array
    {
        return [
            'no approvals left, not work in progress' => [0, false, false],
            'no approvals left, work in progress' => [0, true, false],
            'approvals left, not work in progress' => [2, false, true],
            'approvals left, work in progress' => [2, true, false],
        ];
    }

    /**
     * @dataProvider invokeProvider
     * @param int $approvalsLeft
     * @param bool $isWorkInProgress
     * @param bool $expected
     * @throws \Exception
     */
    public function testInvoke(int $approvalsLeft, bool $isWorkInProgress, bool $expected): void
    {
        $mergeRequestApproval = $this->createMergeRequestApproval([
            'approvals_left' => $approvalsLeft,
            'merge_request' => $this->createMergeRequest([
                'work_in_progress' => $isWorkInProgress,
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]),
        ]);

        $filter = new MergeRequestApprovalFilter();
        $actual = $filter($mergeRequestApproval);

        $this->assertEquals($expected, $actual);
    }

    public function testInvokeWithUser(): void
    {
        $user = $this->createUser();

        $mergeRequestApproval = $this->createMergeRequestApproval([
            'merge_request' => $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]),
        ]);

        $filter = $this->createPartialMock(MergeRequestApprovalFilter::class, ['isUserInvolved']);
        $filter = $filter->addUser($user);
        $filter
            ->expects($this->once())
            ->method('isUserInvolved')
            ->with($mergeRequestApproval)
            ->willReturn(true);
        $actual = $filter($mergeRequestApproval);

        $this->assertTrue($actual);
    }

    public function isUserInvolvedProvider(): array
    {
        return [
            'approver' => [true, false, false, true],
            'assignee' => [false, true, false, true],
            'suggested approver' => [false, false, true, true],
        ];
    }

    /**
     * @dataProvider isUserInvolvedProvider
     * @param bool $isApprover
     * @param bool $isAssignee
     * @param bool $isSuggestedApprover
     * @param bool $expected
     * @throws \Exception
     */
    public function testIsUserInvolved(
        bool $isApprover,
        bool $isAssignee,
        bool $isSuggestedApprover,
        bool $expected
    ): void {
        $user = $this->createUser();

        $mergeRequestApproval = $this->createMergeRequestApproval([
            'merge_request' => $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]),
        ]);

        $filter = $this->createPartialMock(
            MergeRequestApprovalFilter::class,
            ['isApprover', 'isAssignee', 'isSuggestedApprover']
        );
        $filter = $filter->addUser($user);
        if ($isSuggestedApprover) {
            $filter = $filter->addIncludeSuggestedApprovers(true);
        }
        $filter
            ->expects($this->once())
            ->method('isApprover')
            ->with($mergeRequestApproval)
            ->willReturn($isApprover);
        $filter
            ->expects($this->once())
            ->method('isAssignee')
            ->with($mergeRequestApproval)
            ->willReturn($isAssignee);

        $filter
            ->expects($this->once())
            ->method('isSuggestedApprover')
            ->with($mergeRequestApproval)
            ->willReturn($isSuggestedApprover);
        $actual = $filter->isUserInvolved($mergeRequestApproval);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider userProvider
     * @param bool $expectsSuggestedApprover
     * @param bool $expectsUser
     * @param bool $expected
     * @throws \Exception
     */
    public function testIsSuggestedApprover(bool $expectsSuggestedApprover, bool $expectsUser, bool $expected): void
    {
        $user = $this->createUser();
        $suggestedApprovers = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 4); $i++) {
            $suggestedApprovers[] = $this->createUser();
        }
        if ($expectsSuggestedApprover) {
            $suggestedApprovers[] = $user;
        }

        $mergeRequestApproval = $this->createMergeRequestApproval([
            'merge_request' => $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]),
            'suggested_approvers' => $suggestedApprovers,
        ]);

        $filter = new MergeRequestApprovalFilter();
        if ($expectsUser) {
            $filter = $filter->addUser($user);
        }
        $actual = $filter->isSuggestedApprover($mergeRequestApproval);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider userProvider
     * @param bool $expectsApprover
     * @param bool $expectsUser
     * @param bool $expected
     * @throws \Exception
     */
    public function testIsApprover(bool $expectsApprover, bool $expectsUser, bool $expected): void
    {
        $user = $this->createUser();
        $approvers = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 4); $i++) {
            $approvers[] = $this->createUser();
        }
        if ($expectsApprover) {
            $approvers[] = $user;
        }

        $mergeRequestApproval = $this->createMergeRequestApproval([
            'merge_request' => $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]),
            'approvers' => $approvers,
        ]);

        $filter = new MergeRequestApprovalFilter();
        if ($expectsUser) {
            $filter = $filter->addUser($user);
        }
        $actual = $filter->isApprover($mergeRequestApproval);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider userProvider
     * @param bool $expectsAssignee
     * @param bool $expectsUser
     * @param bool $expected
     * @throws \Exception
     */
    public function testIsAssignee(bool $expectsAssignee, bool $expectsUser, bool $expected): void
    {
        $user = $this->createUser();
        $assignee = $this->createUser();
        if ($expectsAssignee) {
            $assignee = $user;
        }

        $mergeRequestApproval = $this->createMergeRequestApproval([
            'merge_request' => $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $assignee,
            ]),
        ]);

        $filter = new MergeRequestApprovalFilter();
        if ($expectsUser) {
            $filter = $filter->addUser($user);
        }
        $actual = $filter->isAssignee($mergeRequestApproval);

        $this->assertEquals($expected, $actual);
    }

    public function userProvider(): array
    {
        return [
            'assignee/approver/suggested approver' => [true, true, true],
            'not assignee/approver/suggested approver' => [false, true, false],
            'no user' => [true, false, false],
        ];
    }
}
