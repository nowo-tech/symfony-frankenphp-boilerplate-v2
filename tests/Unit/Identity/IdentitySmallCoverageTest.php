<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity;

use App\Identity\Entity\PasswordHistory;
use App\Identity\Entity\User;
use App\Identity\Security\Permission;
use App\Identity\UserDisplayPreferenceDefaults;
use DateTime;
use PHPUnit\Framework\TestCase;

final class IdentitySmallCoverageTest extends TestCase
{
    public function testPermissionHelpers(): void
    {
        self::assertFalse(Permission::isDottedCapability('ROLE_ADMIN'));
        self::assertFalse(Permission::isDottedCapability('IS_AUTHENTICATED'));
        self::assertFalse(Permission::isDottedCapability('ROLE_FOO.BAR'));
        self::assertFalse(Permission::isDottedCapability('IS_AUTHENTICATED.FULLY'));
        self::assertFalse(Permission::isDottedCapability('plain'));
        self::assertTrue(Permission::isDottedCapability('admin.users.manage'));
        self::assertTrue(Permission::isKnown(' Admin.Users.Manage '));
        self::assertFalse(Permission::isKnown('not.a.real.permission'));
    }

    public function testPasswordHistoryAccessors(): void
    {
        $user = new User();
        $user->setEmail('u@example.com');
        $user->setDisplayName('U');

        $history = new PasswordHistory();
        $history->setUser($user);
        $history->setPassword('hash');
        $createdAt = new DateTime('2024-02-01 12:00:00');
        $history->setCreatedAt($createdAt);

        self::assertNull($history->getId());
        self::assertSame($user, $history->getUser());
        self::assertSame('hash', $history->getPassword());
        self::assertSame($createdAt, $history->getCreatedAt());
    }

    public function testUserDisplayPreferenceDefaultsFallbackLocale(): void
    {
        $user = new User();
        $user->setEmail('u@example.com');
        $user->setDisplayName('U');
        // Embeddable property defaults are non-null; clear them to exercise applyMissing branches.
        $user->setPreferredContentWidth(null);
        $user->setPreferredUiDensity(null);
        $user->setPreferredFontScale(null);
        $user->setPreferredSidebar(null);

        UserDisplayPreferenceDefaults::applyMissing($user, '   ');

        self::assertSame(UserDisplayPreferenceDefaults::LOCALE, $user->getPreferredLocaleRaw());
        self::assertSame(UserDisplayPreferenceDefaults::CONTENT_WIDTH, $user->getPreferredContentWidthRaw());
        self::assertSame(UserDisplayPreferenceDefaults::UI_DENSITY, $user->getPreferredUiDensityRaw());
        self::assertSame(UserDisplayPreferenceDefaults::FONT_SCALE, $user->getPreferredFontScaleRaw());
        self::assertSame(UserDisplayPreferenceDefaults::SIDEBAR, $user->getPreferredSidebarRaw());
    }
}
