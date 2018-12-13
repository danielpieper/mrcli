<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

class MergeRequest
{

    /** @var int */
    private $id;

    /** @var string */
    private $title;

    /** @var Project */
    private $project;

    /**
     * Project constructor.
     * @param int $id
     * @param string $title
     * @param Project $project
     */
    public function __construct(int $id, string $title, Project $project)
    {
        $this->id = $id;
        $this->title = $title;
        $this->project = $project;
    }

    /**
     * @param array $mergeRequest
     * @return MergeRequest
     */
    public static function fromArray(array $mergeRequest): self
    {
        return new self(
            (int)$mergeRequest['id'],
            (string)$mergeRequest['title'],
            $mergeRequest['project']
        );
    }

    /**
     * @return Project
     */
    public function project(): Project
    {
        return $this->project;
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
    public function title(): string
    {
        return $this->title;
    }
}
