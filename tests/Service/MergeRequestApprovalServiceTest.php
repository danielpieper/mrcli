<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
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
            ->with($this->equalTo($mergeRequest))
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
            ->with($this->equalTo($mergeRequest->getProject()->getId(), $mergeRequest->getId()))
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

    private function createGitlabClientMock(MergeRequest $mergeRequest, array $gitlabMergeRequestApproval)
    {
        $gitlabMergeRequestsMock = $this->createMock(MergeRequests::class);
        $gitlabMergeRequestsMock
            ->expects($this->once())
            ->method('approvals')
            ->with($this->equalTo($mergeRequest->getProject()->getId(), $mergeRequest->getId()))
            ->willReturn($gitlabMergeRequestApproval);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('mergeRequests')
            ->willReturn($gitlabMergeRequestsMock);

        return $gitlabClientMock;
    }
}
