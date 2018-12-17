<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

class MergeRequest
{
    public const STATE_OPENED = 'opened';
    public const STATE_CLOSED = 'closed';
    public const STATE_LOCKED = 'locked';
    public const STATE_MERGED = 'merged';

    /** @var int */
    private $id;

    /** @var int */
    private $iid;

    /** @var string */
    private $title;

    /** @var string */
    private $state;

    /** @var string */
    private $webUrl;

    /** @var Project */
    private $project;

    /** @var User */
    private $author;

    /** @var User */
    private $assignee;

    /**
     * Project constructor.
     * @param int $id
     * @param int $iid
     * @param string $title
     * @param string $state
     * @param string $webUrl
     * @param Project $project
     * @param User $author
     * @param User $assignee
     */
    public function __construct(
        int $id,
        int $iid,
        string $title,
        string $state,
        string $webUrl,
        Project $project,
        User $author,
        User $assignee
    ) {
        $this->id = $id;
        $this->iid = $iid;
        $this->title = $title;
        $this->state = $state;
        $this->webUrl = $webUrl;
        $this->project = $project;
        $this->author = $author;
        $this->assignee = $assignee;
    }

    /**
     * @param array $mergeRequest
     * @return MergeRequest
     */
    public static function fromArray(array $mergeRequest): self
    {
        return new self(
            (int)$mergeRequest['id'],
            (int)$mergeRequest['iid'],
            (string)$mergeRequest['title'],
            (string)$mergeRequest['state'],
            (string)$mergeRequest['web_url'],
            $mergeRequest['project'],
            $mergeRequest['author'],
            $mergeRequest['assignee']
        );
    }

    /**
     * @return int
     */
    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return bool
     */
    public function isOpened(): bool
    {
        return $this->state === self::STATE_OPENED;
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->state === self::STATE_CLOSED;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->state === self::STATE_LOCKED;
    }

    /**
     * @return bool
     */
    public function isMerged(): bool
    {
        return $this->state === self::STATE_MERGED;
    }

    /**
     * @return string
     */
    public function getWebUrl(): string
    {
        return $this->webUrl;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @return User
     */
    public function getAssignee(): User
    {
        return $this->assignee;
    }
}
