<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Transformer;

use DanielPieper\MergeReminder\ValueObject\MergeRequest;
use Razorpay\Slack\Attachment;
use Razorpay\Slack\AttachmentField;

class MergeRequestTransformer
{
    public function transform(MergeRequest $mergeRequest)
    {
        return new Attachment([
            'fallback' => $mergeRequest->getTitle(),
            'text' => $this->translate($mergeRequest, '[:project_name] :title'),
            'author_name' => $mergeRequest->getAuthor()->getUsername(),
            'author_link' => $mergeRequest->getAuthor()->getWebUrl(),
            'author_icon' => $mergeRequest->getAuthor()->getAvatarUrl(),
            'fields' => [
                new AttachmentField([
                    'title' => 'Assignee',
                    'value' => $mergeRequest->getAssignee()->getUsername(),
                    'short' => true
                ])
            ]
        ]);
    }

    private function translate(MergeRequest $mergeRequest, $string)
    {
        return strtr($string, [
            ':project_name' => $mergeRequest->getProject()->getName(),
            ':title' => $mergeRequest->getTitle(),
            ':author_name' => $mergeRequest->getAuthor()->getUsername(),

        ]);
    }
}
