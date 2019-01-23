<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Tests\TestCase;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;

class MergeRequestServiceTest extends TestCase
{
    public function testGetByProjectReturnsMergeRequest()
    {
        $author = $this->createGitlabUser();
        $assignee = $this->createGitlabUser();
        $gitlabMergeRequest = $this->createGitlabMergeRequest([
            'author' => $author,
            'assignee' => $assignee,
        ]);
        $expectedMergeRequest = MergeRequest::fromArray(array_merge(
            $gitlabMergeRequest,
            [
                'project' => $this->createProject(),
                'author' => User::fromArray($author),
                'assignee' => User::fromArray($assignee),
            ]
        ));

        $projectServiceMock = $this->createMock(ProjectService::class);
        $gitlabMergeRequestsMock = $this->createMock(MergeRequests::class);
        $gitlabMergeRequestsMock
            ->expects($this->once())
            ->method('show')
            ->with($this->equalTo($expectedMergeRequest->getProject()->getId(), $expectedMergeRequest->getId()))
            ->willReturn($gitlabMergeRequest);
        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('mergeRequests')
            ->willReturn($gitlabMergeRequestsMock);

        $service = new MergeRequestService($gitlabClientMock, $projectServiceMock);
        $actual = $service->getByProject($expectedMergeRequest->getProject(), $expectedMergeRequest->getId());

        $this->assertEquals($expectedMergeRequest, $actual);
    }


    public function testGetByProjectThrowsException()
    {
        $project = $this->createProject();
        $id = $this->faker->randomNumber();

        $service = $this->createPartialMock(MergeRequestService::class, ['findByProject']);
        $service->expects($this->once())
            ->method('findByProject')
            ->with($this->equalTo($project), $this->equalTo($id))
            ->willReturn(null);

        $this->expectException(MergeRequestNotFoundException::class);
        $service->getByProject($project, $id);
    }
}
