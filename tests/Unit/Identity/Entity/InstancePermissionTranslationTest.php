<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Entity;

use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\InstancePermissionTranslation;
use PHPUnit\Framework\TestCase;

final class InstancePermissionTranslationTest extends TestCase
{
    public function testSettersNormalizeLocaleNameAndDescription(): void
    {
        $translation = new InstancePermissionTranslation();
        $permission = new InstancePermission();

        $translation->setPermission($permission);
        $translation->setLocale(' ES ');
        $translation->setName('  Nombre ');
        $translation->setDescription('  ');

        self::assertNull($translation->getId());
        self::assertSame($permission, $translation->getPermission());
        self::assertSame('es', $translation->getLocale());
        self::assertSame('Nombre', $translation->getName());
        self::assertNull($translation->getDescription());

        $translation->setDescription(' Texto ');
        self::assertSame('Texto', $translation->getDescription());
    }
}
