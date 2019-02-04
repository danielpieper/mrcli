<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use Carbon\Carbon;
use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\Tests\TestCase;
use Razorpay\Slack\Attachment;
use Razorpay\Slack\Client;
use Razorpay\Slack\Message;

class SlackServiceTest extends TestCase
{
    public function testPostMessage(): void
    {
        $mergeRequestsApprovals = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 5); $i++) {
            $mergeRequest = $this->createMergeRequest([
                'project' => $this->createProject(),
                'author' => $this->createUser(),
                'assignee' => $this->createUser(),
            ]);
            $mergeRequestsApprovals[] = $this->createMergeRequestApproval([
                'merge_request' => $mergeRequest,
            ]);
        }

        $slackClient = $this->createMock(Client::class);

        $message = new Message($slackClient);
        $slackClient
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($message);
        $slackClient
            ->expects($this->once())
            ->method('sendMessage')
            ->with($this->anything());
        $service = new SlackService($slackClient);
        $service->postMessage($mergeRequestsApprovals);
    }
    public function testGetAttachmentReturnsAttachment(): void
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);
        $mergeRequestsApproval = $this->createMergeRequestApproval([
            'merge_request' => $mergeRequest,
        ]);

        $service = $this->createPartialMock(SlackService::class, ['getColor', 'getFields']);
        $service
            ->expects($this->once())
            ->method('getFields')
            ->with($mergeRequestsApproval)
            ->willReturn([]);
        $service
            ->expects($this->once())
            ->method('getColor')
            ->with($mergeRequestsApproval)
            ->willReturn('good');

        $method = $this->getMethod($service, 'getAttachment');
        /** @var Attachment $actual */
        $actual = $method->invoke($service, $mergeRequestsApproval);

        $this->assertEquals([
            'title' => $mergeRequest->getTitle(),
            'author_name' => $mergeRequest->getAuthor()->getUsername(),
        ], [
            'title' => $actual->getTitle(),
            'author_name' => $actual->getAuthorName(),
        ]);
    }

    /**
     * @dataProvider getColorProvider
     * @param $days
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetColor($days, $expected): void
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);
        $mergeRequestsApproval = $this->createMergeRequestApproval([
            'merge_request' => $mergeRequest,
            'created_at' => (new Carbon($days . ' days ago'))->toDateString(),
        ]);

        $slackClient = $this->createMock(Client::class);
        $service = new SlackService($slackClient);
        $method = $this->getMethod($service, 'getColor');
        $actual = $method->invoke($service, $mergeRequestsApproval);

        $this->assertEquals($expected, $actual);
    }

    public function getColorProvider(): array
    {
        return [
            'good' => [1, 'good'],
            'warning' => [2, 'warning'],
            'danger' => [3, 'danger'],
        ];
    }

    public function testGetFields(): void
    {
        $mergeRequest = $this->createMergeRequest([
            'project' => $this->createProject(),
            'author' => $this->createUser(),
            'assignee' => $this->createUser(),
        ]);
        $mergeRequestsApproval = $this->createMergeRequestApproval([
            'merge_request' => $mergeRequest,
            'created_at' => $this->faker->dateTimeBetween('-3 month', '-1 month')->format('Y-m-d H:i:s'),
            'approver_groups' => [$this->createGroup()],
            'approvers' => [$this->createUser(), $this->createUser()],
        ]);

        $slackClient = $this->createMock(Client::class);
        $service = new SlackService($slackClient);
        $method = $this->getMethod($service, 'getFields');
        $actual = $method->invoke($service, $mergeRequestsApproval);

        $this->assertEquals($actual[0]['title'], 'Created');
        $this->assertEquals($actual[1]['title'], 'Updated');
        $this->assertEquals($actual[2]['title'], 'Approvers');
        $this->assertEquals($actual[3]['title'], 'Approver Groups');
    }
}
