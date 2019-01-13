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
            'text' => $mergeRequest->getDescription(),
            'author_name' => $mergeRequest->getAuthor()->getUsername(),
            'color' => $this->getColor($mergeRequestApproval),
            'fields' => $this->getFields($mergeRequestApproval),
            'footer' => $project->getName(),
        ]);

        return $attachment;
    }

    private function getFields(MergeRequestApproval $mergeRequestApproval): array
    {
        $fields = [
            [
                'title' => 'Created',
                'value' => $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans(),
                'short' => true,
            ],
        ];
        $approverNames = $this->getApproverNames($mergeRequestApproval);
        if (count($approverNames) > 0) {
            $fields[] = [
                'title' => 'Approvers',
                'value' => implode(', ', $approverNames),
                'short' => true,
            ];
        }

        $approverGroupNames = $this->getApproverGroupNames($mergeRequestApproval);
        if (count($approverGroupNames) > 0) {
            $fields[] = [
                'title' => 'Approver Groups',
                'value' => implode(', ', $approverGroupNames),
                'short' => true,
            ];
        }

        return $fields;
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return array
     */
    private function getApproverGroupNames(MergeRequestApproval $mergeRequestApproval): array
    {
        /** @var Group[] $approverGroups */
        $approverGroups = $mergeRequestApproval->getApproverGroups();

        $result = [];
        foreach ($approverGroups as $approverGroup) {
            $result[] = $approverGroup->getName();
        }

        return $result;
    }

    /**
     * @param MergeRequestApproval $mergeRequestApproval
     * @return array
     */
    private function getApproverNames(MergeRequestApproval $mergeRequestApproval): array
    {
        /** @var User[] $approvers */
        $approvers = $mergeRequestApproval->getApprovers();

        $result = [];
        foreach ($approvers as $approver) {
            $result[] = $approver->getUsername();
        }

        return $result;
    }

    private function getColor(MergeRequestApproval $mergeRequestApproval): string
    {
        $ageInDays = $mergeRequestApproval->getCreatedAt()->diffInDays();

        if ($ageInDays > 2) {
            return 'danger';
        }
        if ($ageInDays > 1) {
            return 'warning';
        }
        return 'good';
    }
}
