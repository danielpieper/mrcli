<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Tests\ApplicationTestCase;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Symfony\Component\Console\Tester\CommandTester;

class ApproverCommandTest extends ApplicationTestCase
{
    public function testExecute()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('authenticated-user.json'));
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse($this->createResponse('mergerequests.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval1.json'));

        $command = $this->application->find('approver');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $actual = $commandTester->getDisplay();
        $expected = <<<EXP
1 Pending merge requests for Approver 3:

test.author
[Test Project] test merge request 1
https://example.org/test/test-project/merge_requests/1
 Created:             3w ago         
 Approvers:           test.approver3 
 Suggested approvers: test.approver4 


EXP;
        $this->assertEquals($expected, $actual);
    }

    public function testGetUser()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('users-approver4.json'));
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse($this->createResponse('mergerequests.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval1.json'));

        $command = $this->application->find('approver');
        $commandTester = new CommandTester($command);

        $this->expectException(MergeRequestApprovalNotFoundException::class);
        $commandTester->execute([
            'command' => $command->getName(),
            'username' => 'test.approver4',
        ]);
    }

    public function testNoPendingMergeRequests()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('authenticated-user.json'));
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse(new Response(
            200,
            ['Content-type' => 'application/json'],
            '[]'
        ));

        $command = $this->application->find('approver');
        $commandTester = new CommandTester($command);

        $this->expectException(MergeRequestNotFoundException::class);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);
    }
}
