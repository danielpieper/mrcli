<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Exception\UserNotFoundException;
use DanielPieper\MergeReminder\Filter\MergeRequestApprovalFilter;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\UserService;
use DanielPieper\MergeReminder\SlackServiceAwareInterface;
use DanielPieper\MergeReminder\SlackServiceAwareTrait;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApproverCommand extends BaseCommand implements SlackServiceAwareInterface
{
    use SlackServiceAwareTrait;

    /** @var UserService */
    private $userService;

    /** @var MergeRequestService */
    private $mergeRequestService;

    /** @var MergeRequestApprovalService */
    private $mergeRequestApprovalService;

    /** @var MergeRequestApprovalFilter */
    private $mergeRequestApprovalFilter;

    public function __construct(
        UserService $userService,
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService,
        MergeRequestApprovalFilter $mergeRequestApprovalFilter
    ) {
        $this->userService = $userService;
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
        $this->mergeRequestApprovalFilter = $mergeRequestApprovalFilter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('approver')
            ->setAliases(['a'])
            ->setDescription('Get approver\'s pending merge requests')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'Gitlab username or email address'
            )->addOption(
                'slack',
                's',
                InputOption::VALUE_NONE,
                'Post to slack channel'
            )->addOption(
                'include-suggested',
                'i',
                InputOption::VALUE_NONE,
                'Include suggested approvers'
            );
    }

    /**
     * @param string|null $username
     * @return User
     * @throws UserNotFoundException
     */
    private function getUser(string $username = null): User
    {
        if ($username) {
            return $this->userService->getByName($username);
        }
        return $this->userService->getAuthenticated();
    }

    /**
     * @return array
     * @throws MergeRequestNotFoundException
     * @throws \Exception
     */
    private function getMergeRequests(): array
    {
        $mergeRequests = $this->mergeRequestService->all();
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
        $user = $this->getUser(/** @scrutinizer ignore-type */ $input->getArgument('username'));
        $mergeRequests = $this->getMergeRequests();
        $mergeRequestApprovals = $this->mergeRequestApprovalService->getAll(
            $mergeRequests,
            $this->mergeRequestApprovalFilter->addUser($user, $input->getOption('include-suggested'))
        );

        $messageText = sprintf(
            '%u Pending merge requests for %s:',
            count($mergeRequestApprovals),
            $user->getName()
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
