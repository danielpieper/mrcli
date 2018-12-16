<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use Razorpay\Slack\Client;

class SlackService
{
    /** @var Client */
    private $slackClient;

    public function __construct(Client $slackClient)
    {
        $this->slackClient = $slackClient;
    }

    public function postMessage()
    {
        $message = $this->slackClient->createMessage();
        $message->setText('test');

        $this->slackClient->sendMessage($message);
    }
}
