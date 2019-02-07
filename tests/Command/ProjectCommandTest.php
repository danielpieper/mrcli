<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Tests\ApplicationTestCase;
use Http\Client\HttpClient;
use Symfony\Component\Console\Tester\CommandTester;

class ProjectCommandTest extends ApplicationTestCase
{
    public function testExecute()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse($this->createResponse('mergerequests.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval1.json'));

        $command = $this->application->find('project');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'names' => ['Test Project'],
        ]);

        $actual = $commandTester->getDisplay();
        $expected = <<<EXP
1 Pending merge requests for projects Test Project:

test.author
[Test Project] test merge request 1
https://example.org/test/test-project/merge_requests/1
 Created:             15h ago        
 Approvers:           test.approver3 
 Suggested approvers: test.approver4 


EXP;
        $this->assertEquals($expected, $actual);
    }
}
