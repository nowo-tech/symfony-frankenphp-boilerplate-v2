<?php

declare(strict_types=1);

namespace App\Tests\Unit\Setup;

use App\Identity\Entity\User;
use App\Identity\Repository\UserRepository;
use App\Setup\AdminUserProvisioner;
use DateTime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminUserProvisionerTest extends TestCase
{
    public function testAdminExistsDelegatesToCount(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::atLeastOnce())->method('count')->with([])->willReturn(2);

        self::assertTrue(new AdminUserProvisioner($users, $this->createStub(UserPasswordHasherInterface::class))->adminExists());
    }

    public function testCreateAdminHashesPasswordAndSaves(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('save')->with(self::callback(static fn (User $user): bool => 'admin@example.com' === $user->getEmail()
            && 'admin' === $user->getDisplayName()
            && \in_array('ROLE_ADMIN', $user->getRoles(), true)
            && 'hashed' === $user->getPassword()
            && $user->getPasswordChangedAt() instanceof DateTime));

        new AdminUserProvisioner($users, $hasher)->createAdmin([
            'email' => 'admin@example.com',
            'password' => 'secret',
        ]);
    }

    public function testCreateAdminUsesProvidedRolesAndFallbackDisplayName(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('save')->with(self::callback(static fn (User $user): bool => 'Admin' === $user->getDisplayName()
            && \in_array('ROLE_ADMIN', $user->getRoles(), true)
            && \in_array('ROLE_USER', $user->getRoles(), true)));

        new AdminUserProvisioner($users, $hasher)->createAdmin([
            'email' => '@orphan.local',
            'password' => 'secret',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
        ]);
    }

    public function testCreateAdminDefaultsEmptyRolesToAdmin(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed');

        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('save')->with(self::callback(static fn (User $user): bool => \in_array('ROLE_ADMIN', $user->getRoles(), true)));

        new AdminUserProvisioner($users, $hasher)->createAdmin([
            'email' => 'x@example.com',
            'password' => 'secret',
            'roles' => [],
        ]);
    }
}
