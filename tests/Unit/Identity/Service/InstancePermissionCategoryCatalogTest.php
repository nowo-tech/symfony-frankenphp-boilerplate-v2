<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Service;

use App\Identity\Service\InstancePermissionCategoryCatalog;
use PHPUnit\Framework\TestCase;

final class InstancePermissionCategoryCatalogTest extends TestCase
{
    public function testSlugsAreNonEmptyAndIncludeCustom(): void
    {
        $slugs = InstancePermissionCategoryCatalog::slugs();
        self::assertNotEmpty($slugs);
        self::assertContains('custom', $slugs);
        self::assertContains('general', $slugs);
        self::assertContains('access', $slugs);
        self::assertContains('overview', $slugs);
    }

    public function testFormChoicesMapTranslationKeysToSlugs(): void
    {
        $choices = InstancePermissionCategoryCatalog::formChoices();
        self::assertSame('access', $choices['permissions.category.access.name']);
        self::assertSame('custom', $choices['permissions.category.custom.name']);
        self::assertCount(\count(InstancePermissionCategoryCatalog::slugs()), $choices);
    }

    public function testIsKnown(): void
    {
        self::assertTrue(InstancePermissionCategoryCatalog::isKnown('instance'));
        self::assertFalse(InstancePermissionCategoryCatalog::isKnown('unknown'));
    }
}
