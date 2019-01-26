<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Tests\Service;

use DanielPieper\MergeReminder\Exception\UserNotFoundException;
use DanielPieper\MergeReminder\Service\UserService;
use DanielPieper\MergeReminder\Tests\TestCase;
use DanielPieper\MergeReminder\ValueObject\User;
use Gitlab\Api\Users;
use Gitlab\Client;

class UserServiceTest extends TestCase
{
    public function testGetReturnsUser()
    {
        $user = $this->createGitlabUser();
        $expectedUser = User::fromArray($user);

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('show')
            ->with($expectedUser->getId())
            ->willReturn($user);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);
        $actual = $service->get($expectedUser->getId());

        $this->assertEquals($expectedUser, $actual);
    }

    public function testGetThrowsException()
    {
        $id = $this->faker->randomNumber();

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('show')
            ->with($id)
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);

        $this->expectException(UserNotFoundException::class);
        $service->get($id);
    }

    public function testGetByNameReturnsUser()
    {
        $user = $this->createGitlabUser();
        $expectedUser = User::fromArray($user);

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('all')
            ->with([
                'username' => $expectedUser->getUsername(),
                'active' => true,
                'blocked' => false,
            ])
            ->willReturn([$user]);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);
        $actual = $service->getByName($expectedUser->getUsername());

        $this->assertEquals($expectedUser, $actual);
    }

    public function testGetByNameThrowsException()
    {
        $username = $this->faker->userName();

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('all')
            ->with([
                'username' => $username,
                'active' => true,
                'blocked' => false,
            ])
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);

        $this->expectException(UserNotFoundException::class);
        $service->getByName($username);
    }

    public function testGetAll()
    {
        $users = $expectedUsers = [];
        for ($i = 0; $i < $this->faker->numberBetween(2, 4); $i++) {
            $user = $this->createGitlabUser();
            $users[] = $user;
            $expectedUsers[] = User::fromArray($user);
        }

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('all')
            ->with($this->anything())
            ->willReturn($users);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);
        $actual = $service->all();

        $this->assertEquals($expectedUsers, $actual);
    }

    public function testGetAllReturnsEmpty()
    {
        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('all')
            ->with($this->anything())
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);
        $actual = $service->all();

        $this->assertEquals([], $actual);
    }

    public function testGetAuthenticated()
    {
        $user = $this->createGitlabUser();
        $expectedUser = User::fromArray($user);

        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);
        $actual = $service->getAuthenticated();

        $this->assertEquals($expectedUser, $actual);
    }

    public function testGetAuthenticatedThrowsException()
    {
        $gitLabUsersMock = $this->createMock(Users::class);
        $gitLabUsersMock
            ->expects($this->once())
            ->method('user')
            ->willReturn(null);

        $gitlabClientMock = $this->createMock(Client::class);
        $gitlabClientMock
            ->expects($this->once())
            ->method('users')
            ->willReturn($gitLabUsersMock);

        $service = new UserService($gitlabClientMock);

        $this->expectException(UserNotFoundException::class);
        $service->getAuthenticated();
    }
}
