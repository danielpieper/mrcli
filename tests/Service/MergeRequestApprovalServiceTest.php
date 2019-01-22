<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\ValueObject\Group;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Faker\Factory;
use Faker\Generator;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use PHPUnit\Framework\TestCase;

class MergeRequestApprovalServiceTest extends TestCase
{
    /** @var Generator */
    private $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->faker = Factory::create('de_DE');
    }

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

    private function createGitlabProject(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->domainName,
            'merge_requests_enabled' => true,
        ];
    }

    private function createGitlabUser(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'username' => $this->faker->userName,
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'state' => User::STATE_ACTIVE,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ];
    }

    private function createGitlabGroup(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ];
    }

    private function createGitlabMergeRequestApproval(
        array $approvedBy = [],
        array $approvers = [],
        array $suggestedApprovers = [],
        array $approverGroups = []
    ): array {
        $mapUser = function (array $user) {
            return [
                'user' => array_merge(
                    $this->createGitlabUser(),
                    $user
                )
            ];
        };
        $approvedBy = array_map($mapUser, $approvedBy);
        $approvers = array_map($mapUser, $approvers);
        $suggestedApprovers = array_map($mapUser, $suggestedApprovers);
        $approverGroups = array_map(function (array $group) {
            return [
                'group' => array_merge(
                    $this->createGitlabGroup(),
                    $group
                )
            ];
        }, $approverGroups);

        return [
            'merge_status' => MergeRequestApproval::MERGE_STATUS_CAN_BE_MERGED,
            'approvals_required' => $this->faker->numberBetween(1, 4),
            'approvals_left' => $this->faker->numberBetween(1, 3),
            'approved_by' => $approvedBy,
            'approver_groups' => $approverGroups,
            'approvers' => $approvers,
            'suggested_approvers' => $suggestedApprovers,
            'updated_at' => $this->faker->dateTimeThisMonth->format('Y-m-d H:i:s'),
            'created_at' => $this->faker->dateTimeThisMonth->format('Y-m-d H:i:s'),
        ];
    }

    private function createGitlabMergeRequest(
        array $project = [],
        array $author = [],
        array $assignee = []
    ): array {
        $project = Project::fromArray(array_merge($this->createGitlabProject(), $project));
        $author = User::fromArray(array_merge($this->createGitlabUser(), $author));
        $assignee = User::fromArray(array_merge($this->createGitlabUser(), $assignee));
        return [
            'id' => $this->faker->randomNumber(),
            'iid' => $this->faker->randomNumber(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'state' => MergeRequest::STATE_OPENED,
            'web_url' => $this->faker->url,
            'work_in_progress' => false,
            'project' => $project,
            'author' => $author,
            'assignee' => $assignee,
        ];
    }
}
