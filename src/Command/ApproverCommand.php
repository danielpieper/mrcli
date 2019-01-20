<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Exception\MergeRequestNotFoundException;
use DanielPieper\MergeReminder\Exception\UserNotFoundException;
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

    public function __construct(
        UserService $userService,
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService
    ) {
        $this->userService = $userService;
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
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
     * @param array $mergeRequests
     * @param User $user
     * @param bool $includeSuggestedApprovers
     * @return array
     * @throws MergeRequestApprovalNotFoundException
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function getMergeRequestApprovals(
        array $mergeRequests,
        User $user,
        bool $includeSuggestedApprovers = false
    ): array {
        $mergeRequestApprovals = [];
        foreach ($mergeRequests as $mergeRequest) {
            $mergeRequestApprovals[] = $this->mergeRequestApprovalService->get($mergeRequest);
        }
        $mergeRequestApprovals = array_filter(
            $mergeRequestApprovals,
            function (MergeRequestApproval $item) use ($user, $includeSuggestedApprovers) {
                $hasApprovalsLeft = $item->getApprovalsLeft() > 0;
                $isWorkInProgress = $item->getMergeRequest()->isWorkInProgress();
                $isApprover = in_array($user->getUsername(), $item->getApproverNames());
                $isAssignee = $item->getMergeRequest()->getAssignee() == $user->getUsername();
                $isSuggestedApprover = $includeSuggestedApprovers
                    && in_array($user->getUsername(), $item->getSuggestedApproverNames()) ;

                return $hasApprovalsLeft && !$isWorkInProgress && ($isApprover || $isAssignee || $isSuggestedApprover);
            }
        );
        if (count($mergeRequestApprovals) == 0) {
            throw new MergeRequestApprovalNotFoundException('No pending merge request approvals.');
        }
        return $mergeRequestApprovals;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->getUser($input->getArgument('username'));
        $mergeRequests = $this->getMergeRequests();
        $mergeRequestApprovals = $this->getMergeRequestApprovals(
            $mergeRequests,
            $user,
            $input->getOption('include-suggested')
        );

        usort($mergeRequestApprovals, function (MergeRequestApproval $approvalA, MergeRequestApproval $approvalB) {
            if ($approvalA->getCreatedAt()->equalTo($approvalB->getCreatedAt())) {
                return 0;
            }
            return ($approvalA->getCreatedAt()->lessThan($approvalB->getCreatedAt()) ? -1 : 1);
        });

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
