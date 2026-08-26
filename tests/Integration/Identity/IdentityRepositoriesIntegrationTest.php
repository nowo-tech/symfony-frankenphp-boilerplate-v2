<?php

declare(strict_types=1);

namespace App\Tests\Integration\Identity;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use App\Identity\Entity\UserAction;
use App\Identity\Entity\UserGroup;
use App\Identity\Entity\UserGroupMembership;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Repository\InstanceRoleRepository;
use App\Identity\Repository\UserActionRepository;
use App\Identity\Repository\UserGroupMembershipRepository;
use App\Identity\Repository\UserGroupRepository;
use App\Identity\Repository\UserRepository;
use App\Identity\UserActionType;
use App\Tests\Support\DatabaseWebTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class IdentityRepositoriesIntegrationTest extends DatabaseWebTestCase
{
    public function testOrderedLookupsHydrationAndCounts(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setDisplayName('Admin');
        $admin->setPassword('x');
        $admin->setRoles(['ROLE_ADMIN']);

        $member = new User();
        $member->setEmail('member@example.com');
        $member->setDisplayName('Member');
        $member->setPassword('x');

        $permission = new InstancePermission();
        $permission->setKey('custom.coverage.probe');
        $permission->setName('Coverage probe');
        $permission->setCategory('access');

        $role = new InstanceRole();
        $role->setName('Support');
        $role->setCode('ROLE_SUPPORT');
        $role->setDescription('Help desk');
        $role->setEnabled(true);
        $role->addPermission($permission);
        $member->addInstanceRole($role);

        $group = new UserGroup();
        $group->setName('Ops');
        $group->setSlug('ops');
        $group->setDescription('Operations');

        $membership = new UserGroupMembership();
        $membership->setUserGroup($group);
        $membership->setUser($member);

        $action = new UserAction();
        $action->setAction(UserActionType::UserCreated);
        $action->setActor($admin);
        $action->setSubjectUser($member);
        $action->setContext(['group_uuid' => $group->getUuid()]);

        $em->persist($admin);
        $em->persist($member);
        $em->persist($permission);
        $em->persist($role);
        $em->persist($group);
        $em->persist($membership);
        $em->persist($action);
        $em->flush();

        $roles = self::getContainer()->get(InstanceRoleRepository::class);
        $permissions = self::getContainer()->get(InstancePermissionRepository::class);
        $users = self::getContainer()->get(UserRepository::class);
        $groups = self::getContainer()->get(UserGroupRepository::class);
        $memberships = self::getContainer()->get(UserGroupMembershipRepository::class);
        $actions = self::getContainer()->get(UserActionRepository::class);

        self::assertNotEmpty($roles->findAllOrderedForIndex());
        self::assertNotEmpty($roles->findAllOrderedForIndex('Support'));
        self::assertSame([], $roles->findAllOrderedForIndex('zzz-no-match'));
        self::assertNotEmpty($roles->findAllOrdered());
        $roles->hydrateDetail($role);
        self::assertSame(1, $roles->countAssignedUsers($role));
        self::assertContains('custom.coverage.probe', $roles->findPermissionKeysForUserId((int) $member->getId()));

        self::assertNotEmpty($permissions->findAllOrdered());
        self::assertNotEmpty($permissions->findAllOrdered('coverage'));
        self::assertContains('custom.coverage.probe', $permissions->findAllKeys());

        self::assertNotEmpty($users->findAllForAdminDirectory());
        self::assertNotEmpty($users->findAllForAdminDirectory('member'));
        self::assertSame(1, $users->countAdmins());
        self::assertSame(1, $users->countAdmins(true));

        self::assertNotEmpty($groups->findAllOrdered());
        self::assertNotEmpty($groups->findAllOrdered('Ops'));
        self::assertSame($group, $groups->findOneBySlug('OPS'));
        $groups->hydrateMembers($group);
        $groups->hydrateAudit($group);

        self::assertNotEmpty($memberships->findByUser($member));
        self::assertSame(
            [(int) $group->getId() => 1],
            $memberships->countByGroupIds([(int) $group->getId()]),
        );

        self::assertNotEmpty($actions->findLatest(10));
        self::assertNotEmpty($actions->findForUser($member, [], UserActionType::UserCreated));
        self::assertSame(
            [],
            $actions->findForUser($member, [UserActionType::GroupCreated], UserActionType::UserCreated),
        );
        self::assertNotEmpty($actions->findForUser(
            $member,
            [UserActionType::UserCreated],
            UserActionType::UserCreated,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+1 day'),
        ));
        self::assertNotEmpty($actions->findForGroup(
            $group,
            [UserActionType::UserCreated],
            UserActionType::UserCreated,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+1 day'),
        ));
        self::assertSame([], $actions->findForGroup($group, []));

        unset($client);
    }
}
