<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests;

use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Faker\Factory;
use Faker\Generator;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /** @var Generator */
    protected $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->faker = Factory::create('de_DE');
    }

    protected function createGitlabProject(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->domainName,
            'merge_requests_enabled' => true,
        ];
    }

    protected function createGitlabUser(): array
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

    protected function createGitlabGroup(): array
    {
        return [
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ];
    }

    protected function createGitlabMergeRequestApproval(
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

    protected function createGitlabMergeRequest(
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
