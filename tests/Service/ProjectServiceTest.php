<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\Tests\TestCase;
use DanielPieper\MergeReminder\ValueObject\Project;
use Gitlab\Api\Projects;
use Gitlab\Api\Users;
use Gitlab\Client;
use Gitlab\ResultPager;

class ProjectServiceTest extends TestCase
{
    public function testGetReturnsProject()
    {
        $project = $this->createGitlabProject();
        $expectedProject = Project::fromArray($project);

        $gitlabProjectsMock = $this->createMock(Projects::class);
        $gitlabProjectsMock
            ->expects($this->once())
            ->method('show')
            ->with($expectedProject->getId())
            ->willReturn($project);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('projects')
            ->willReturn($gitlabProjectsMock);

        $resultPagerMock = $this->createMock(ResultPager::class);

        $service = new ProjectService($gitlabClientMock, $resultPagerMock);
        $actual = $service->get($expectedProject->getId());

        $this->assertEquals($expectedProject, $actual);
    }

    public function testGetThrowsException()
    {
        $id = $this->faker->randomNumber();

        $gitlabProjectsMock = $this->createMock(Projects::class);
        $gitlabProjectsMock
            ->expects($this->once())
            ->method('show')
            ->with($id)
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('projects')
            ->willReturn($gitlabProjectsMock);

        $resultPagerMock = $this->createMock(ResultPager::class);

        $service = new ProjectService($gitlabClientMock, $resultPagerMock);

        $this->expectException(ProjectNotFoundException::class);
        $service->get($id);
    }

    public function testAllByUserReturnsEmptyResult()
    {
        $user = $this->createUser();

        $gitlabUserMock = $this->createMock(Users::class);
        $gitlabUserMock
            ->expects($this->once())
            ->method('usersProjects')
            ->with($user->getId())
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitlabUserMock);

        $resultPagerMock = $this->createMock(ResultPager::class);

        $service = new ProjectService($gitlabClientMock, $resultPagerMock);
        $actual = $service->allByUser($user);

        $this->assertEquals([], $actual);
    }

    public function testAllByUserReturnsProjects()
    {
        $user = $this->createUser();
        $projects = $expected = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 5); $i++) {
            $project = $this->createGitlabProject();
            $projects[] = $project;
            $expected[] = Project::fromArray($project);
        }

        $gitlabUserMock = $this->createMock(Users::class);
        $gitlabUserMock
            ->expects($this->once())
            ->method('usersProjects')
            ->with($user->getId())
            ->willReturn($projects);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitlabUserMock);

        $resultPagerMock = $this->createMock(ResultPager::class);

        $service = new ProjectService($gitlabClientMock, $resultPagerMock);
        $actual = $service->allByUser($user);

        $this->assertEquals($expected, $actual);
    }

    public function testAllReturnsProjects()
    {
        $projects = $expected = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 5); $i++) {
            $project = $this->createGitlabProject();
            $projects[] = $project;
            $expected[] = Project::fromArray($project);
        }

        $resultPagerMock = $this->createMock(ResultPager::class);
        $resultPagerMock
            ->expects($this->once())
            ->method('fetchAll')
            ->withAnyParameters()
            ->willReturn($projects);

        $gitlabProjectsMock = $this->createMock(Projects::class);
        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('projects')
            ->willReturn($gitlabProjectsMock);

        $service = new ProjectService($gitlabClientMock, $resultPagerMock);
        $actual = $service->all();

        $this->assertEquals($expected, $actual);
    }
}
