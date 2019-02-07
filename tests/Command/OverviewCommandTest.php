<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Tests\ApplicationTestCase;
use Http\Client\HttpClient;
use Symfony\Component\Console\Tester\CommandTester;

class OverviewCommandTest extends ApplicationTestCase
{
    public function testExecute()
    {
        $httpClient = $this->container->get(HttpClient::class);
        $httpClient->addResponse($this->createResponse('projects.json'));
        $httpClient->addResponse($this->createResponse('mergerequests.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval1.json'));
        $httpClient->addResponse($this->createResponse('mergerequest-approval2.json'));

        $command = $this->application->find('overview');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $actual = $commandTester->getDisplay();
        $expected = <<<EXP
+----------------+-------+--------------+
| Approver       | Total | Test Project |
+----------------+-------+--------------+
| test.approver3 | 2     | 2            |
| test.approver4 | 1     | 1            |
+----------------+-------+--------------+

EXP;
        $this->assertEquals($expected, $actual);
    }
}
