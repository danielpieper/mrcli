<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
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

    public function testFind()
    {
        $mergeRequest = MergeRequest::fromArray($this->createGitlabMergeRequest());
        $gitlabMergeRequestApproval = $this->createGitlabMergeRequestApproval();

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

        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        // TODO: improve assertions for transform
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

    private function createGitlabMergeRequestApproval(): array
    {
        return [
            'merge_status' => MergeRequestApproval::MERGE_STATUS_CAN_BE_MERGED,
            'approvals_required' => $this->faker->numberBetween(1, 4),
            'approvals_left' => $this->faker->numberBetween(1, 3),
            'approved_by' => [],
            'approver_groups' => [],
            'approvers' => [
                ['user' => $this->createGitlabUser()],
                ['user' => $this->createGitlabUser()],
                ['user' => $this->createGitlabUser()],
            ],
            'suggested_approvers' => [],
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
