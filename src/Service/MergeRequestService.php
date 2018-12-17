<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;

class MergeRequestService
{
    /** @var \Gitlab\Client */
    private $gitlabClient;

    public function __construct(\Gitlab\Client $gitlabClient)
    {
        $this->gitlabClient = $gitlabClient;
    }

    /**
     * @param Project $project
     * @param int $id
     * @return MergeRequest|null
     */
    public function find(Project $project, int $id): MergeRequest
    {
        $mergeRequest = $this->gitlabClient->mergeRequests()->show($project->getId(), $id);
        if (!$mergeRequest) {
            return null;
        }
        return $this->transform($project, $mergeRequest);
    }

    /**
     * @param Project $project
     * @param int $id
     * @return MergeRequest
     * @throws MergeRequestNotFoundException
     */
    public function get(Project $project, int $id): MergeRequest
    {
        $mergeRequest = $this->find($project, $id);
        if (!$mergeRequest) {
            throw new MergeRequestNotFoundException();
        }
        return $mergeRequest;
    }

    /**
     * @param Project $project
     * @return MergeRequest[]
     */
    public function all(Project $project): array
    {
        $mergeRequests = $this->gitlabClient->mergeRequests()->all($project->getId());

        return array_map(function ($mergeRequest) use ($project) {
            return $this->transform($project, $mergeRequest);
        }, $mergeRequests);
    }

    /**
     * @param Project $project
     * @param array $mergeRequest
     * @return MergeRequest
     */
    private function transform(Project $project, array $mergeRequest): MergeRequest
    {
        $mergeRequest['project'] = $project;
        $mergeRequest['author'] = User::fromArray($mergeRequest['author']);
        $mergeRequest['assignee'] = User::fromArray($mergeRequest['assignee']);
        return MergeRequest::fromArray($mergeRequest);
    }
}
