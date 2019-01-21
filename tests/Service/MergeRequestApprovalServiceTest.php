<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder;

use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use DanielPieper\MergeReminder\ValueObject\Project;
use DanielPieper\MergeReminder\ValueObject\User;
use Faker\Factory;
use Faker\Generator;
use Gitlab\Api\MergeRequests;
use Gitlab\Client;
use PHPUnit\Framework\TestCase;

class MergeRequestApprovalServiceTest extends TestCase
{
    /** @var Generator */
    private $faker;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->faker = Factory::create('de_DE');
    }

    public function testFindReturnsNull()
    {
        $mergeRequest = $this->getMergeRequest();

        $gitlabMergeRequestsMock = $this->createMock(MergeRequests::class);
        $gitlabMergeRequestsMock
            ->expects($this->once())
            ->method('approvals')
            ->with($this->equalTo($mergeRequest->getProject()->getId(), $mergeRequest->getId()))
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('mergeRequests')
            ->willReturn($gitlabMergeRequestsMock);

        $service = new MergeRequestApprovalService($gitlabClientMock);
        $actual = $service->find($mergeRequest);

        $this->assertNull($actual);
    }

    /**
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected function getMethod($name)
    {
        $class = new \ReflectionClass('\DanielPieper\MergeReminder\Command\ApproverCommand');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    private function getProject(): Project
    {
        return Project::fromArray([
            'id' => $this->faker->randomNumber(),
            'name' => $this->faker->domainName,
            'merge_requests_enabled' => true,
        ]);
    }

    private function getUser(): User
    {
        return User::fromArray([
            'id' => $this->faker->randomNumber(),
            'username' => $this->faker->userName,
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'state' => User::STATE_ACTIVE,
            'avatar_url' => $this->faker->imageUrl(),
            'web_url' => $this->faker->url(),
        ]);
    }

    private function getMergeRequest(): MergeRequest
    {
        return MergeRequest::fromArray([
            'id' => $this->faker->randomNumber(),
            'iid' => $this->faker->randomNumber(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'state' => MergeRequest::STATE_OPENED,
            'web_url' => $this->faker->url,
            'work_in_progress' => false,
            'project' => $this->getProject(),
            'author' => $this->getUser(),
            'assignee' => $this->getUser(),
        ]);
    }
}
