<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\Tests\ApplicationTestCase;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ProjectCommandTest extends ApplicationTestCase
{
    public function testExecute()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse($this->createResponse('mergerequests.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval1.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval2.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval3.json'));

        $command = $this->application->find('project');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'names' => ['Test Project'],
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $actual = $commandTester->getDisplay();
        $expected = <<<EXP
3 Pending merge requests for projects Test Project:

test.author
[Test Project] test merge request 1
https://example.org/test/test-project/merge_requests/1
this is a test merge request
 Created:             3w ago         
 Updated:             1w ago         
 Approvers:           test.approver3 
 Suggested approvers: test.approver4 

test.author2
[Test Project] test merge request 2
https://example.org/test/test-project/merge_requests/2
this is a test merge request #2
 Created:             2d ago                         
 Approvers:           test.approver3, test.approver4 
 Suggested approvers: test.approver1, test.approver2 

test.author2
[Test Project] test merge request 3
https://example.org/test/test-project/merge_requests/3
this is a test merge request #3
 Created:             8h ago                         
 Approvers:           test.approver3, test.approver4 
 Suggested approvers: test.approver1, test.approver2 


EXP;
        $this->assertEquals($expected, $actual);
    }

    public function testNoPendingMergeRequests()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse(new Response(
            200,
            ['Content-type' => 'application/json'],
            '[]'
        ));

        $command = $this->application->find('project');
        $commandTester = new CommandTester($command);

        $this->expectException(MergeRequestNotFoundException::class);
        $commandTester->execute([
            'command' => $command->getName(),
            'names' => ['Test Project'],
        ]);
    }

    public function testProjectNotFound()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('projects.json'));

        $command = $this->application->find('project');
        $commandTester = new CommandTester($command);

        $this->expectException(ProjectNotFoundException::class);
        $commandTester->execute([
            'command' => $command->getName(),
            'names' => ['does-not-exist'],
        ]);
    }
}
