<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\Client;
use Gitlab\ResultPager;

class ProjectService
{
    /** @var Client */
    private $gitlabClient;

    /** @var ResultPager */
    private $resultPager;

    public function __construct(Client $gitlabClient, ResultPager $resultPager)
    {
        $this->gitlabClient = $gitlabClient;
        $this->resultPager = $resultPager;
    }

    /**
     * @param int $id
     * @return Project|null
     */
    public function find(int $id): ?Project
    {
        $project = $this->gitlabClient->projects()->show($id);
        if (!is_array($project)) {
            return null;
        }
        return Project::fromArray($project);
    }

    /**
     * @param int $id
     * @return Project
     * @throws ProjectNotFoundException
     */
    public function get(int $id): Project
    {
        $project = $this->find($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }
        return $project;
    }

    public function allByUser(User $user): array
    {
        $projects = $this->gitlabClient->users()->usersProjects($user->getId());
        if (!is_array($projects)) {
            return [];
        }

        return array_map(function ($project) {
            return Project::fromArray($project);
        }, $projects);
    }

    /**
     * @return Project[]
     */
    public function all(): array
    {
        $projects = $this->resultPager->fetchAll(
            $this->gitlabClient->projects(),
            'all',
            [
                [
                    'page' => 1,
                    'per_page' => 100,
                    'archived' => false,
                    'with_merge_requests_enabled' => true,
                ]
            ]
        );

        return array_map(function ($project) {
            return Project::fromArray($project);
        }, $projects);
    }
}
