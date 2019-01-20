<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use Carbon\Carbon;
use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\ResultPager;

class MergeRequestService
{
    /** @var \Gitlab\Client */
    private $gitlabClient;

    /** @var ProjectService */
    private $projectService;

    public function __construct(\Gitlab\Client $gitlabClient, ProjectService $projectService)
    {
        $this->gitlabClient = $gitlabClient;
        $this->projectService = $projectService;
    }

    /**
     * @param Project $project
     * @param int $id
     * @return MergeRequest|null
     */
    public function findByProject(Project $project, int $id): MergeRequest
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
    public function getByProject(Project $project, int $id): MergeRequest
    {
        $mergeRequest = $this->findByProject($project, $id);
        if (!$mergeRequest) {
            throw new MergeRequestNotFoundException();
        }
        return $mergeRequest;
    }

    /**
     * @param Carbon|null $createdAfter
     * @return MergeRequest[]
     * @throws \Exception
     */
    public function all(?Carbon $createdAfter = null): array
    {
        if (!$createdAfter) {
            $createdAfter = new Carbon('1 month ago');
        }

        $parameters = [
            'page' => 1,
            'per_page' => 100,
            'state' => 'opened',
            'scope' => 'all',
            'created_after' => $createdAfter,
        ];

        $pager = new ResultPager($this->gitlabClient);
        $mergeRequests = $pager->fetchAll(
            $this->gitlabClient->mergeRequests(),
            'all',
            [
                null,
                $parameters,
            ]
        );

        $projects = [];
        foreach ($this->projectService->all() as $project) {
            $projects[$project->getId()] = $project;
        }

        return array_map(function ($mergeRequest) use ($projects) {
            return $this->transform($projects[$mergeRequest['project_id']], $mergeRequest);
        }, $mergeRequests);
    }

    /**
     * @param Project $project
     * @param Carbon|null $createdAfter
     * @return MergeRequest[]
     * @throws \Exception
     */
    public function allByProject(Project $project, ?Carbon $createdAfter = null): array
    {
        if (!$createdAfter) {
            $createdAfter = new Carbon('1 month ago');
        }

        $parameters = [
            'page' => 1,
            'per_page' => 100,
            'state' => 'opened',
            'created_after' => $createdAfter,
        ];
        $pager = new ResultPager($this->gitlabClient);
        $mergeRequests = $pager->fetchAll(
            $this->gitlabClient->mergeRequests(),
            'all',
            [
                $project->getId(),
                $parameters,
            ]
        );

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
        if ($mergeRequest['assignee']) {
            $mergeRequest['assignee'] = User::fromArray($mergeRequest['assignee']);
        }
        return MergeRequest::fromArray($mergeRequest);
    }
}
