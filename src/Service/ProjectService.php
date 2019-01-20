<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\ResultPager;

class ProjectService
{
    /** @var \Gitlab\Client */
    private $gitlabClient;

    public function __construct(\Gitlab\Client $gitlabClient)
    {
        $this->gitlabClient = $gitlabClient;
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
        $pager = new ResultPager($this->gitlabClient);
        $projects = $pager->fetchAll(
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
