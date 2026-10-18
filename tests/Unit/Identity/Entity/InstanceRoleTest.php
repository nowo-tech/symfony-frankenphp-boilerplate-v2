<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Entity;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use PHPUnit\Framework\TestCase;
use stdClass;

final class InstanceRoleTest extends TestCase
{
    public function testSettersNormalizeCodeAndDescription(): void
    {
        $role = new InstanceRole();
        $role->setName('  Support  ');
        $role->setCode('support');
        $role->setDescription('  ');
        $role->setEnabled(false);
        $role->setSystem(true);

        self::assertNull($role->getId());
        self::assertNotSame('', $role->getUuid());
        self::assertSame('Support', $role->getName());
        self::assertSame('ROLE_SUPPORT', $role->getCode());
        self::assertNull($role->getDescription());
        self::assertFalse($role->isEnabled());
        self::assertTrue($role->isSystem());

        $role->setCode('ROLE_ALREADY');
        self::assertSame('ROLE_ALREADY', $role->getCode());
        $role->setDescription(' Helps users ');
        self::assertSame('Helps users', $role->getDescription());
    }

    public function testPermissionCollectionAndLookup(): void
    {
        $role = new InstanceRole();
        $permission = new InstancePermission();
        $permission->setKey('admin.users.manage');

        $role->addPermission($permission);
        $role->addPermission($permission);
        self::assertCount(1, $role->getPermissions());
        self::assertTrue($role->hasPermissionKey(' Admin.Users.Manage '));
        self::assertFalse($role->hasPermissionKey('missing.key'));

        $role->removePermission($permission);
        self::assertCount(0, $role->getPermissions());

        $role->addPermission($permission);
        $role->clearPermissions();
        self::assertCount(0, $role->getPermissions());
    }

    public function testAuditBlameAcceptsOnlyUsers(): void
    {
        $role = new InstanceRole();
        $user = new User();
        $user->setEmail('blame@example.com');

        $role->setCreatedBy($user);
        $role->setUpdatedBy(new stdClass());
        self::assertSame($user, $role->getCreatedBy());
        self::assertNull($role->getUpdatedBy());

        $role->setUpdatedBy($user);
        self::assertSame($user, $role->getUpdatedBy());
        self::assertCount(0, $role->getUsers());
    }
}
