<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Repository;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use App\Identity\Entity\UserGroup;
use App\Identity\Entity\UserGroupMembership;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Repository\InstanceRoleRepository;
use App\Identity\Repository\UserGroupMembershipRepository;
use App\Identity\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class IdentityRepositoryHelpersTest extends TestCase
{
    public function testInstanceRoleFindOneByCodeNormalizesPrefix(): void
    {
        $role = new InstanceRole();
        $repo = $this->partialRepo(InstanceRoleRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'ROLE_SUPPORT'])
            ->willReturn($role);

        self::assertSame($role, $repo->findOneByCode('support'));
    }

    public function testInstanceRoleFindOneByCodeKeepsExistingPrefix(): void
    {
        $repo = $this->partialRepo(InstanceRoleRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'ROLE_ADMIN'])
            ->willReturn(null);

        self::assertNull($repo->findOneByCode('ROLE_ADMIN'));
    }

    public function testInstancePermissionFindOneByKeyNormalizes(): void
    {
        $permission = new InstancePermission();
        $repo = $this->partialRepo(InstancePermissionRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['key' => 'admin.users.manage'])
            ->willReturn($permission);

        self::assertSame($permission, $repo->findOneByKey(' Admin.Users.Manage '));
    }

    public function testUserFindOneByEmailNormalizes(): void
    {
        $user = new User();
        $repo = $this->partialRepo(UserRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'person@example.com'])
            ->willReturn($user);

        self::assertSame($user, $repo->findOneByEmail(' Person@Example.com '));
    }

    public function testUserFindOneBySlackUserIdRejectsBlank(): void
    {
        $repo = $this->partialRepo(UserRepository::class);
        $repo->expects(self::never())->method('findOneBy');

        self::assertNull($repo->findOneBySlackUserId('   '));
    }

    public function testUserFindOneBySlackUserIdTrims(): void
    {
        $user = new User();
        $repo = $this->partialRepo(UserRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['slackUserId' => 'U123'])
            ->willReturn($user);

        self::assertSame($user, $repo->findOneBySlackUserId(' U123 '));
    }

    public function testUserSavePersistsAndOptionallyFlushes(): void
    {
        $user = new User();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::never())->method('flush');

        $repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repo->method('getEntityManager')->willReturn($em);

        $repo->save($user, false);
    }

    public function testUserSaveFlushesByDefault(): void
    {
        $user = new User();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $repo = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repo->method('getEntityManager')->willReturn($em);

        $repo->save($user);
    }

    public function testMembershipFindOneByGroupAndUserDelegates(): void
    {
        $group = new UserGroup();
        $user = new User();
        $membership = new UserGroupMembership();

        $repo = $this->partialRepo(UserGroupMembershipRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['userGroup' => $group, 'user' => $user])
            ->willReturn($membership);

        self::assertSame($membership, $repo->findOneByGroupAndUser($group, $user));
    }

    public function testMembershipCountByGroupIdsEmptyReturnsEmptyMap(): void
    {
        $repo = $this->getMockBuilder(UserGroupMembershipRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        self::assertSame([], $repo->countByGroupIds([]));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T&\PHPUnit\Framework\MockObject\MockObject
     */
    private function partialRepo(string $class): object
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
    }
}
