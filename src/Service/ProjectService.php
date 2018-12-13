<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\ValueObject\Project;

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
     * @return Project
     * @throws ProjectNotFoundException
     */
    public function get(int $id): Project
    {
        $project = $this->gitlabClient->projects()->show($id);
        if (!$project) {
            throw new ProjectNotFoundException();
        }
        return Project::fromArray($project);
    }

    /**
     * @return Project[]
     */
    public function all(): array
    {
        $projects = $this->gitlabClient->projects()->all();

        return array_map(function ($project) {
            return Project::fromArray($project);
        }, $projects);
    }
}
