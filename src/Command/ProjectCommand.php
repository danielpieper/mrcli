<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Exception\ProjectNotFoundException;
use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\ProjectService;
use DanielPieper\MergeReminder\SlackServiceAwareInterface;
use DanielPieper\MergeReminder\SlackServiceAwareTrait;
use DanielPieper\MergeReminder\ValueObject\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectCommand extends BaseCommand implements SlackServiceAwareInterface
{
    use SlackServiceAwareTrait;

    /** @var ProjectService */
    private $projectService;

    /** @var MergeRequestService */
    private $mergeRequestService;

    /** @var MergeRequestApprovalService */
    private $mergeRequestApprovalService;

    /** @var MergeRequestApprovalFilter */
    private $mergeRequestApprovalFilter;

    public function __construct(
        ProjectService $projectService,
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService,
        MergeRequestApprovalFilter $mergeRequestApprovalFilter
    ) {
        $this->projectService = $projectService;
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
        $this->mergeRequestApprovalFilter = $mergeRequestApprovalFilter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('project')
            ->setAliases(['p'])
            ->setDescription('Get pending merge-requests by projects')
            ->addArgument(
                'names',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Gitlab project names (separate by space)'
            )->addOption(
                'slack',
                's',
                InputOption::VALUE_NONE,
                'Post to slack channel'
            );
    }

    /**
     * @param array $names
     * @return array
     * @throws ProjectNotFoundException
     */
    private function getProjects(array $names): array
    {
        $projects = $this->projectService->all();
        $projects = array_filter($projects, function (Project $project) use ($names) {
            return in_array($project->getName(), $names);
        });
        if (count($projects) == 0) {
            throw new ProjectNotFoundException('Projects not found.');
        }
        return $projects;
    }

    /**
     * @param array $projects
     * @return array
     * @throws MergeRequestNotFoundException
     */
    private function getMergeRequests(array $projects): array
    {
        $mergeRequests = [];
        foreach ($projects as $project) {
            $mergeRequests = array_merge($mergeRequests, $this->mergeRequestService->allByProject($project));
        }
        if (count($mergeRequests) == 0) {
            throw new MergeRequestNotFoundException('No pending merge requests.');
        }
        return $mergeRequests;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectNames = $input->getArgument('names');
        $projects = $this->getProjects(/** @scrutinizer ignore-type */ $projectNames);
        $mergeRequests = $this->getMergeRequests($projects);
        $mergeRequestApprovals = $this->mergeRequestApprovalService->getByMergeRequests(
            $mergeRequests,
            $this->mergeRequestApprovalFilter
        );

        $messageText = sprintf(
            '%u Pending merge requests for projects %s:',
            count($mergeRequestApprovals),
            implode(', ', $projectNames)
        );

        if (!$output->isQuiet()) {
            $output->writeln([$messageText, '']);
            foreach ($mergeRequestApprovals as $mergeRequestApproval) {
                $this->printMergeRequestApproval($output, $mergeRequestApproval);
            }
        }

        if ($input->getOption('slack')) {
            if (!$this->slackService) {
                $output->writeln('<error>Slack is not configured,'
                    . ' please specify SLACK_WEBHOOK_URL and SLACK_CHANNEL environment variables.</error>');
                return;
            }
            $this->slackService->postMessage($mergeRequestApprovals, $messageText);
        }
    }
}
