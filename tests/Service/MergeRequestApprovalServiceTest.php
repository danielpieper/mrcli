<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Tests\TestCase;
use DanielPieper\MergeReminder\ValueObject\Group;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;

class MergeRequestApprovalServiceTest extends TestCase
{
    public function testFindApproversDoNotContainApprovedBy()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);

        $approvers = $expectedApprovers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabUser = $this->createGitlabUser();
            $approvers[] = ['user' => $gitlabUser];
            $expectedApprovers[] = User::fromArray($gitlabUser);
        }
        $approvedBy = [
            ['user' => $this->createGitlabUser()],
        ];
        $approvers += $approvedBy;

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval([
            'approvers' => $approvers,
            'approved_by' => $approvedBy,
        ]);

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedApprovers, $actual->getApprovers());
    }

    public function testFindSuggestedApproversDoNotContainApprovers()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);

        $suggestedApprovers = $expectedSuggestedApprovers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabUser = $this->createGitlabUser();
            $suggestedApprovers[] = ['user' => $gitlabUser];
            $expectedSuggestedApprovers[] = User::fromArray($gitlabUser);
        }
        $approvers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $approvers[] = ['user' => $this->createGitlabUser()];
        }
        $suggestedApprovers += $approvers;

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval([
            'approvers' => $approvers,
            'suggested_approvers' => $suggestedApprovers,
        ]);

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedSuggestedApprovers, $actual->getSuggestedApprovers());
    }

    public function testFindReturnsApproverGroups()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);

        $approverGroups = $expectedApproverGroups = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabGroup = $this->createGitlabGroup();
            $approverGroups[] = ['group' => $gitlabGroup];
            $expectedApproverGroups[] = Group::fromArray($gitlabGroup);
        }

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval([
            'approver_groups' => $approverGroups,
        ]);

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedApproverGroups, $actual->getApproverGroups());
    }

    public function testGetReturnsMergeRequest()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);
        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval();

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->get($mergeRequest);

        $this->assertEquals($mergeRequest, $actual->getMergeRequest());
    }


    public function testGetThrowsException()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);

        $service = $this->createPartialMock(MergeRequestApprovalService::class, ['find']);
        $service->expects($this->once())
            ->method('find')
            ->with($mergeRequest)
            ->willReturn(null);

        $this->expectException(MergeRequestApprovalNotFoundException::class);
        $service->get($mergeRequest);
    }


    public function testFindReturnsNull()
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);

        $gitlabMergeRequestsMock = $this->createMock(MergeRequests::class);
        $gitlabMergeRequestsMock
            ->expects($this->once())
            ->method('approvals')
            ->with($mergeRequest->getProject()->getId(), $mergeRequest->getIid())
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('mergeRequests')
            ->willReturn($gitlabMergeRequestsMock);

        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertNull($actual);
    }

    public function testGetAllSortByCreatedAt()
    {
        $mergeRequestApprovals = $expectedMergeRequestApprovals = $mergeRequests = [];
        $date = $this->faker->dateTimeThisDecade();
        for ($i = 0; $i < $this->faker->numberBetween(5, 10); $i++) {
            $mergeRequest = $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]);
            $mergeRequests[] = $mergeRequest;
            $expectedMergeRequestApprovals[] = $mergeRequestApprovals[] = $this->createMergeRequestApproval([
                'created_at' => $date->format('Y-m-d H:i:s'),
                'merge_request' => $mergeRequest,
            ]);
            $date->add(new \DateInterval('P' . $this->faker->numberBetween(1, 10) . 'D'));
        }
        shuffle($mergeRequestApprovals);

        $filter = $this->createMock(MergeRequestApprovalFilter::class);
        $filter
            ->expects($this->any())
            ->method('__invoke')
            ->with($this->anything())
            ->willReturn(true);

        $service = $this->createPartialMock(MergeRequestApprovalService::class, ['find']);
        $service
            ->expects($this->exactly(count($mergeRequests)))
            ->method('find')
            ->withConsecutive(...$mergeRequests)
            ->willReturnOnConsecutiveCalls(...$mergeRequestApprovals);

        $actualMergeRequestApprovals = $service->getAll($mergeRequests, $filter);

        $this->assertEquals($expectedMergeRequestApprovals, $actualMergeRequestApprovals);
    }

    public function testGetAllThrowsException()
    {
        $mergeRequests = [
             $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
             ]),
            $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ])
        ];

        $service = $this->createPartialMock(MergeRequestApprovalService::class, ['find']);
        $service->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(...$mergeRequests)
            ->willReturn(null);

        $filter = $this->createMock(MergeRequestApprovalFilter::class);
        $filter
            ->expects($this->any())
            ->method('__invoke')
            ->with($this->anything())
            ->willReturn(true);

        $this->expectException(MergeRequestApprovalNotFoundException::class);
        $service->getAll($mergeRequests, $filter);
    }

    private function createGitlabClientMock(MergeRequest $mergeRequest, array $gitlabMergeRequestApproval)
    {
        $gitlabMergeRequestsMock = $this->createMock(MergeRequests::class);
        $gitlabMergeRequestsMock
            ->expects($this->once())
            ->method('approvals')
            ->with($mergeRequest->getProject()->getId(), $mergeRequest->getIid())
            ->willReturn($gitlabMergeRequestApproval);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('mergeRequests')
            ->willReturn($gitlabMergeRequestsMock);

        return $gitlabClientMock;
    }
}
