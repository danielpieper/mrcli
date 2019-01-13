<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

class Group
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $avatarUrl;

    /** @var string */
    private $webUrl;

    /**
     * Project constructor.
     * @param int $id
     * @param string $name
     * @param string $avatarUrl
     * @param string $webUrl
     */
    public function __construct(
        int $id,
        string $name,
        string $avatarUrl,
        string $webUrl
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->avatarUrl = $avatarUrl;
        $this->webUrl = $webUrl;
    }

    /**
     * @param array $group
     * @return Group
     */
    public static function fromArray(array $group): self
    {
        return new self(
            (int)$group['id'],
            (string)$group['name'],
            (string)$group['avatar_url'],
            (string)$group['web_url']
        );
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    /**
     * @return string
     */
    public function getWebUrl(): string
    {
        return $this->webUrl;
    }
}
