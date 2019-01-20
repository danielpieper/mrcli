<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Exception\MergeRequestApprovalNotFoundException;
use DanielPieper\MergeReminder\Exception\UserNotFoundException;
use DanielPieper\MergeReminder\Service\MergeRequestApprovalService;
use DanielPieper\MergeReminder\Service\MergeRequestService;
use DanielPieper\MergeReminder\Service\SlackService;
use DanielPieper\MergeReminder\Service\UserService;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ApproverCommand extends BaseCommand
{
    /** @var UserService */
    private $userService;

    /** @var MergeRequestService */
    private $mergeRequestService;

    /** @var MergeRequestApprovalService */
    private $mergeRequestApprovalService;

    /** @var SlackService */
    private $slackService;

    public function __construct(
        UserService $userService,
        MergeRequestService $mergeRequestService,
        MergeRequestApprovalService $mergeRequestApprovalService,
        SlackService $slackService
    ) {
        $this->userService = $userService;
        $this->mergeRequestService = $mergeRequestService;
        $this->mergeRequestApprovalService = $mergeRequestApprovalService;
        $this->slackService = $slackService;
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
            )
            ->addOption(
                'slack',
                's',
                InputOption::VALUE_NONE,
                'Post to slack channel'
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
     * @param array $mergeRequests
     * @param User $user
     * @return array
     * @throws MergeRequestApprovalNotFoundException
     */
    private function getMergeRequestApprovals(array $mergeRequests, User $user): array
    {
        $mergeRequestApprovals = [];
        foreach ($mergeRequests as $mergeRequest) {
            $mergeRequestApprovals[] = $this->mergeRequestApprovalService->get($mergeRequest);
        }
        return array_filter($mergeRequestApprovals, function (MergeRequestApproval $item) use ($user) {
            $hasApprovalsLeft = $item->getApprovalsLeft() > 0;
            $isWorkInProgress = $item->getMergeRequest()->isWorkInProgress();
            $isApprover = in_array($user->getUsername(), $item->getApproverNames());
            $isAssignee = $item->getMergeRequest()->getAssignee() == $user->getUsername();

            return $hasApprovalsLeft && !$isWorkInProgress && ($isApprover || $isAssignee);
        });
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

        $mergeRequests = $this->mergeRequestService->all();
        if (count($mergeRequests) == 0) {
            $output->writeln('No pending merge requests.');
            return;
        }

        $mergeRequestApprovals = $this->getMergeRequestApprovals($mergeRequests, $user);
        if (count($mergeRequestApprovals) == 0) {
            $output->writeln('No pending merge request approvals.');
            return;
        }

        usort($mergeRequestApprovals, function (MergeRequestApproval $approvalA, MergeRequestApproval $approvalB) {
            if ($approvalA->getCreatedAt()->equalTo($approvalB->getCreatedAt())) {
                return 0;
            }
            return ($approvalA->getCreatedAt()->lessThan($approvalB->getCreatedAt()) ? -1 : 1);
        });

        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $this->printMergeRequestApproval($output, $mergeRequestApproval);
        }

        if ($input->getOption('slack')) {
            $this->slackService->postMessage($mergeRequestApprovals);
        }
    }
}
