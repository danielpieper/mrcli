<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Transformer;

use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
use DanielPieper\MergeReminder\ValueObject\User;
use Razorpay\Slack\ActionConfirmation;
use Razorpay\Slack\Attachment;
use Razorpay\Slack\AttachmentAction;
use Razorpay\Slack\Message;

class MergeRequestApprovalTransformer
{
    /**
     * @param Message $message
     * @param MergeRequestApproval $mergeRequestApproval
     * @return Message
     */
    public function transform(Message $message, MergeRequestApproval $mergeRequestApproval): Message
    {
        $mergeRequest = $mergeRequestApproval->getMergeRequest();
        $project = $mergeRequest->getProject();

        $message->setText(implode("\n", [
            strtr('[:project] :title', [
                ':project' => $project->getName(),
                ':title' => '*' . $mergeRequest->getTitle() . '*',
            ]),
            strtr(':created_at - Waiting on :approvers', [
                ':created_at' => $mergeRequestApproval->getCreatedAt()->shortRelativeToNowDiffForHumans(),
                ':approvers' => $this->getApprovers($mergeRequestApproval)
            ]),
        ]));

        $attachment = new Attachment([
            'fallback' => $mergeRequest->getWebUrl(),
            'actions' => [
                [
                    'type' => 'button',
                    'text' => 'Review',
                    'url' => $mergeRequest->getWebUrl(),
                    'style' => 'primary',
                ],
            ],
        ]);

        $message->attach($attachment);

        return $message;
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
}
