<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\Project;

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
     * @return MergeRequest
     * @throws MergeRequestNotFoundException
     */
    public function get(Project $project, int $id): MergeRequest
    {
        $mergeRequest = $this->gitlabClient->mergeRequests()->show($project->id(), $id);
        if (!$mergeRequest) {
            throw new MergeRequestNotFoundException();
        }
        $mergeRequest['project'] = $project;
        return MergeRequest::fromArray($mergeRequest);
    }

    /**
     * @param Project $project
     * @return MergeRequest[]
     */
    public function all(Project $project): array
    {
        $mergeRequests = $this->gitlabClient->mergeRequests()->all($project->id());

        return array_map(function ($mergeRequest) use ($project) {
            $mergeRequest['project'] = $project;
            return MergeRequest::fromArray($mergeRequest);
        }, $mergeRequests);
    }
}
