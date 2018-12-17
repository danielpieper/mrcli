<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Transformer\MergeRequestApprovalTransformer;
use DanielPieper\MergeReminder\ValueObject\MergeRequestApproval;
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

        $transformer = new MergeRequestApprovalTransformer();

        $texts = [];
        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $texts[] = $transformer->transform($mergeRequestApproval);
        }

        $message->setText(implode("\n\n", $texts));

        $this->slackClient->sendMessage($message);
    }
}
