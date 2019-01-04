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
        $transformer = new MergeRequestApprovalTransformer();

        foreach ($mergeRequestApprovals as $mergeRequestApproval) {
            $message = $this->slackClient->createMessage();
            $message = $transformer->transform($message, $mergeRequestApproval);
//            var_dump($message);
//            return;

            $this->slackClient->sendMessage($message);
        }
    }
}
