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
    private $description;

    /** @var string */
    private $webUrl;

    /** @var bool */
    private $isWorkInProgress;

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
     * @param string $description
     * @param string $webUrl
     * @param bool $isWorkInProgress
     * @param Project $project
     * @param User $author
     * @param User $assignee
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        int $id,
        int $iid,
        string $title,
        string $description,
        string $webUrl,
        bool $isWorkInProgress,
        Project $project,
        User $author,
        ?User $assignee = null
    ) {
        $this->id = $id;
        $this->iid = $iid;
        $this->title = $title;
        $this->description = $description;
        $this->webUrl = $webUrl;
        $this->isWorkInProgress = $isWorkInProgress;
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
            (string)$mergeRequest['description'],
            (string)$mergeRequest['web_url'],
            (bool)$mergeRequest['work_in_progress'],
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
    public function getDescription(): string
    {
        return $this->description;
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
     * @return User|null
     */
    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    /**
     * @return bool
     */
    public function isWorkInProgress(): bool
    {
        return $this->isWorkInProgress;
    }
}
