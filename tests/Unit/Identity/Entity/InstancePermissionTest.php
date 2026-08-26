<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Entity;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstancePermissionTranslation;
use App\Identity\Entity\User;
use PHPUnit\Framework\TestCase;

final class InstancePermissionTest extends TestCase
{
    public function testSettersNormalizeKeyCategoryAndDescription(): void
    {
        $permission = new InstancePermission();
        $permission->setKey(' Admin.Users.Manage ');
        $permission->setName('  Manage users ');
        $permission->setDescription('  ');
        $permission->setCategory(' Access ');
        $permission->setSystem(true);

        self::assertNull($permission->getId());
        self::assertSame('admin.users.manage', $permission->getKey());
        self::assertSame('Manage users', $permission->getName());
        self::assertNull($permission->getDescription());
        self::assertSame('access', $permission->getCategory());
        self::assertTrue($permission->isSystem());
        self::assertCount(0, $permission->getRoles());

        $permission->setDescription(' Can manage users ');
        self::assertSame('Can manage users', $permission->getDescription());
    }

    public function testTranslationsAndLocaleHelpers(): void
    {
        $permission = new InstancePermission();
        $es = new InstancePermissionTranslation();
        $es->setLocale('ES');
        $es->setName('Gestionar');
        $es->setDescription('Desc');

        $permission->addTranslation($es);
        $permission->addTranslation($es);
        self::assertCount(1, $permission->getTranslations());
        self::assertSame($permission, $es->getPermission());
        self::assertSame($es, $permission->findTranslation('es'));
        self::assertSame('Gestionar', $permission->getNameForLocale('es'));
        self::assertSame('Desc', $permission->getDescriptionForLocale('es'));
        self::assertNull($permission->getNameForLocale('fr'));
        self::assertNull($permission->getDescriptionForLocale('fr'));

        $emptyName = new InstancePermissionTranslation();
        $emptyName->setLocale('de');
        $emptyName->setName('  ');
        $permission->addTranslation($emptyName);
        self::assertNull($permission->getNameForLocale('de'));

        $permission->removeTranslation($es);
        self::assertNull($es->getPermission());
        self::assertNull($permission->findTranslation('es'));
    }

    public function testSyncTranslationsAddsUpdatesAndRemoves(): void
    {
        $permission = new InstancePermission();
        $permission->syncTranslations(
            ['en' => 'Manage', 'es' => 'Gestionar', 'fr' => ''],
            ['en' => 'Desc', 'es' => '', 'fr' => ''],
        );

        self::assertCount(2, $permission->getTranslations());
        self::assertSame('Manage', $permission->getNameForLocale('en'));
        self::assertSame('Desc', $permission->getDescriptionForLocale('en'));
        self::assertSame('Gestionar', $permission->getNameForLocale('es'));
        self::assertNull($permission->getDescriptionForLocale('es'));

        $permission->syncTranslations(['en' => 'Updated'], ['en' => 'New desc']);
        self::assertCount(1, $permission->getTranslations());
        self::assertSame('Updated', $permission->getNameForLocale('en'));
        self::assertSame('New desc', $permission->getDescriptionForLocale('en'));
        self::assertNull($permission->findTranslation('es'));
    }

    public function testAuditBlameAcceptsOnlyUsers(): void
    {
        $permission = new InstancePermission();
        $user = new User();
        $user->setEmail('audit@example.com');

        $permission->setCreatedBy($user);
        $permission->setUpdatedBy(new \stdClass());
        self::assertSame($user, $permission->getCreatedBy());
        self::assertNull($permission->getUpdatedBy());
        $permission->setUpdatedBy($user);
        self::assertSame($user, $permission->getUpdatedBy());
    }
}
