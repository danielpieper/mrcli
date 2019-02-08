<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Service;

use DanielPieper\MergeReminder\Exception\UserNotFoundException;
use DanielPieper\MergeReminder\ValueObject\User;

class UserService
{
    /** @var \Gitlab\Client */
    private $gitlabClient;

    public function __construct(\Gitlab\Client $gitlabClient)
    {
        $this->gitlabClient = $gitlabClient;
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User
    {
        $user = $this->gitlabClient->users()->show($id);
        if (!is_array($user)) {
            return null;
        }
        return User::fromArray($user);
    }

    /**
     * @param string $username
     * @return User
     */
    public function findByName(string $username): ?User
    {
        $users = $this->all($username);
        if (count($users) == 0) {
            return null;
        }

        return array_shift($users);
    }

    /**
     * @return User
     * @throws UserNotFoundException
     */
    public function getAuthenticated(): User
    {
        $user = $this->gitlabClient->users()->user();
        if (!is_array($user)) {
            throw new UserNotFoundException();
        }
        return User::fromArray($user);
    }

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function get(int $id): User
    {
        $user = $this->find($id);
        if (!$user) {
            throw new UserNotFoundException();
        }
        return $user;
    }

    /**
     * @param string $username
     * @return User
     * @throws UserNotFoundException
     */
    public function getByName(string $username): User
    {
        $user = $this->findByName($username);
        if (!$user) {
            throw new UserNotFoundException();
        }
        return $user;
    }

    /**
     * @param string|null $username
     * @return array
     */
    public function all(string $username = null): array
    {
        $parameters = ['active' => true];
        if ($username) {
            $parameters['username'] = $username;
        }
        $users = $this->gitlabClient->users()->all($parameters);
        if (!is_array($users)) {
            return [];
        }

        return array_map(function ($user) {
            return User::fromArray($user);
        }, $users);
    }
}
