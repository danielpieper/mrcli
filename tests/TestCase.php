<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests;

use DanielPieper\MergeReminder\ValueObject\Group;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Faker\Factory;
use Faker\Generator;
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

    protected function createGitlabProject(array $attributes = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->domainName,
            'merge_requests_enabled' => true,
        ], $attributes);
    }

    protected function createProject(array $attributes = [])
    {
        return Project::fromArray($this->createGitlabProject($attributes));
    }

    protected function createGitlabUser(array $attributes = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'username' => $this->faker->userName,
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'state' => User::STATE_ACTIVE,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ], $attributes);
    }

    protected function createUser(array $attributes = [])
    {
        return User::fromArray($this->createGitlabUser($attributes));
    }

    protected function createGitlabGroup(array $attributes = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ], $attributes);
    }

    protected function createGroup(array $attributes = [])
    {
        return Group::fromArray($this->createGitlabGroup($attributes));
    }

    protected function createGitlabMergeRequestApproval(array $attributes = []): array
    {
        return array_merge([
            'merge_status' => MergeRequestApproval::MERGE_STATUS_CAN_BE_MERGED,
            'approvals_required' => $this->faker->numberBetween(1, 4),
            'approvals_left' => $this->faker->numberBetween(1, 3),
            'updated_at' => $this->faker->dateTimeThisMonth->format('Y-m-d H:i:s'),
            'created_at' => $this->faker->dateTimeThisMonth->format('Y-m-d H:i:s'),
            'approved_by' => [],
            'approver_groups' => [],
            'approvers' => [],
            'suggested_approvers' => [],
        ], $attributes);
    }

    protected function createMergeRequestApproval(array $attributes = [])
    {
        return MergeRequestApproval::fromArray($this->createGitlabMergeRequestApproval($attributes));
    }

    protected function createGitlabMergeRequest(array $attributes = []): array
    {
        return array_merge([
            'id' => $this->faker->randomNumber(),
            'iid' => $this->faker->randomNumber(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'state' => MergeRequest::STATE_OPENED,
            'web_url' => $this->faker->url,
            'work_in_progress' => false,
//            'project' => null,
//            'author' => null,
//            'assignee' => null,
        ], $attributes);
    }

    protected function createMergeRequest(array $attributes = [])
    {
        return MergeRequest::fromArray($this->createGitlabMergeRequest($attributes));
    }
}
