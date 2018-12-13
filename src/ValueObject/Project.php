<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

class Project
{

    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var bool */
    private $isMergeRequestsEnabled;

    /**
     * Project constructor.
     * @param int $id
     * @param string $name
     * @param bool $isMergeRequestsEnabled
     */
    public function __construct(int $id, string $name, bool $isMergeRequestsEnabled)
    {
        $this->id = $id;
        $this->name = $name;
        $this->isMergeRequestsEnabled = $isMergeRequestsEnabled;
    }

    /**
     * @param array $project
     * @return Project
     */
    public static function fromArray(array $project): self
    {
        return new self(
            (int)$project['id'],
            (string)$project['name'],
            (bool)$project['merge_requests_enabled']
        );
    }

    /**
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isMergeRequestsEnabled(): bool
    {
        return $this->isMergeRequestsEnabled;
    }
}
