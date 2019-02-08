<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\ValueObject;

class User
{
    public const STATE_ACTIVE = 'active';
    public const STATE_BLOCKED = 'blocked';

    /** @var int */
    private $id;

    /** @var string */
    private $username;

    /** @var string */
    private $name;

    /** @var string */
    private $webUrl;

    /**
     * Project constructor.
     * @param int $id
     * @param string $username
     * @param string $name
     * @param string $webUrl
     */
    public function __construct(
        int $id,
        string $username,
        string $name,
        string $webUrl
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->name = $name;
        $this->webUrl = $webUrl;
    }

    /**
     * @param array $user
     * @return User
     */
    public static function fromArray(array $user): self
    {
        return new self(
            (int)$user['id'],
            (string)$user['username'],
            (string)$user['name'],
            (string)$user['web_url']
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
    public function getUsername(): string
    {
        return $this->username;
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
    public function getWebUrl(): string
    {
        return $this->webUrl;
    }
}
