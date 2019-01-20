<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder;

use DanielPieper\MergeReminder\Service\SlackService;

interface SlackServiceAwareInterface
{
    /**
     * @param SlackService $slackService
     */
    public function setSlackService(SlackService $slackService): void;
}
