<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder;

use DanielPieper\MergeReminder\Service\SlackService;

/**
 * Basic Implementation of SlackServiceAwareInterface.
 */
trait SlackServiceAwareTrait
{
    /** @var SlackService */
    protected $slackService;

    public function setSlackService(SlackService $slackService): void
    {
        $this->slackService = $slackService;
    }
}
