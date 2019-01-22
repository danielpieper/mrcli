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
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());

        $approvers = $expectedApprovers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabUser = $this->createGitlabUser();
            $approvers[] = $gitlabUser;
            $expectedApprovers[] = User::fromArray($gitlabUser);
        }
        $approvedBy = [$this->createGitlabUser()];
        $approvers += $approvedBy;

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval(
            $approvedBy,
            $approvers
        );

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedApprovers, $actual->getApprovers());
    }

    public function testFindSuggestedApproversDoNotContainApprovers()
    {
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());

        $suggestedApprovers = $expectedSuggestedApprovers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabUser = $this->createGitlabUser();
            $suggestedApprovers[] = $gitlabUser;
            $expectedSuggestedApprovers[] = User::fromArray($gitlabUser);
        }
        $approvers = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $approvers[] = $this->createGitlabUser();
        }
        $suggestedApprovers += $approvers;

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval(
            [],
            $approvers,
            $suggestedApprovers
        );

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedSuggestedApprovers, $actual->getSuggestedApprovers());
    }

    public function testFindReturnsApproverGroups()
    {
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());

        $approverGroups = $expectedApproverGroups = [];
        for ($i = 0; $i < $this->faker->numberBetween(1, 3); $i++) {
            $gitlabGroup = $this->createGitlabGroup();
            $approverGroups[] = $gitlabGroup;
            $expectedApproverGroups[] = Group::fromArray($gitlabGroup);
        }

        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval(
            [],
            [],
            [],
            $approverGroups
        );

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertEquals($expectedApproverGroups, $actual->getApproverGroups());
    }

    public function testGetReturnsMergeRequest()
    {
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());
        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval();

        $gitlabClientMock = $this->createGitlabClientMock($mergeRequest, $gitlabMergeRequestApproval);
        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->get($mergeRequest);

        $this->assertEquals($mergeRequest, $actual->getMergeRequest());
    }


    public function testGetThrowsException()
    {
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());

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
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());

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
