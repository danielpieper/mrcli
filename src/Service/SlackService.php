<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Transformer\MergeRequestApprovalTransformer;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use Razorpay\Slack\Attachment;
use Razorpay\Slack\Client;

class SlackService
{
    /** @var Client */
    private $slackClient;

    public function __construct(Client $slackClient)
    {
        $this->slackClient = $slackClient;
    }

    /**
     * @param MergeRequestApproval[] $mergeRequestApprovals
     */
    public function postMessage(array $mergeRequestApprovals)
    {
        $message = $this->slackClient->createMessage();
        $message->setText('Your pending merge requests');

        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $attachment = $this->getAttachment($mergeRequestApproval);
            $message->attach($attachment);
        }

        $this->slackClient->sendMessage($message);
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return Attachment
     */
    private function getAttachment(MergeRequestApproval $mergeRequestApproval): Attachment
    {
        $mergeRequest = $mergeRequestApproval->getMergeRequest();
        $project = $mergeRequest->getProject();

        $attachment = new Attachment([
            'fallback' => $mergeRequest->getWebUrl(),
            'title' => $mergeRequest->getTitle(),
            'title_link' => $mergeRequest->getWebUrl(),
            'author_name' => $mergeRequest->getAuthor()->getUsername(),
            'color' => $this->getColor($mergeRequestApproval),
            'fields' => [
                [
                    'title' => 'Approvers',
                    'value' => $this->getApprovers($mergeRequestApproval),
                    'short' => true,
                ],
                [
                    'title' => 'Created',
                    'value' => $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans(),
                    'short' => true,
                ],
            ],
            'footer' => $project->getName(),
        ]);

        return $attachment;
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return string
     */
    private function getApprovers(MergeRequestApproval $mergeRequestApproval): string
    {
        /** @var User[] $approvers */
        $approvers = $mergeRequestApproval->getApprovers();

        $result = [];
        foreach ($approvers as $approver) {
            $result[] = $approver->getUsername();
        }

        return implode(' ', $result);
    }

    private function getColor(MergeRequestApproval $mergeRequestApproval): string
    {
        $ageInDays = $mergeRequestApproval->getCreatedAt()->diffInDays();

        if ($ageInDays > 3) {
            return 'danger';
        }
        if ($ageInDays > 1) {
            return 'warning';
        }
        return 'good';
    }
}
