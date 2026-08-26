<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity;

use App\Identity\Entity\Embeddable\UserUiPreferences;
use App\Identity\Entity\User;
use App\Identity\UserDisplayPreferenceDefaults;
use PHPUnit\Framework\TestCase;

final class UserUiPreferencesTest extends TestCase
{
    public function testUserFacadesDelegateToEmbeddable(): void
    {
        $user = new User();
        $user->setPreferredTheme('dark');
        $user->setPreferredContentWidth('full');
        $user->setPushNotificationsEnabled(true);

        self::assertSame('dark', $user->getPreferredTheme());
        self::assertSame('full', $user->getPreferredContentWidth());
        self::assertTrue($user->isPushNotificationsEnabled());
        self::assertSame('dark', $user->getUiPreferences()->getPreferredTheme());
    }

    public function testResetForAnonymizeClearsAllUiPrefs(): void
    {
        $prefs = new UserUiPreferences();
        $prefs->setPreferredLocale('es');
        $prefs->setPreferredTheme('dark');
        $prefs->setPreferredContentWidth('full');
        $prefs->setPreferredUiDensity('compact');
        $prefs->setPreferredFontScale('lg');
        $prefs->setPreferredSidebar('collapsed');
        $prefs->setPreferredCollapsedIssuePanels(['payload']);
        $prefs->markProductTourSeen();
        $prefs->setPushNotificationsEnabled(true);

        $prefs->resetForAnonymize();

        self::assertNull($prefs->getPreferredLocaleRaw());
        self::assertNull($prefs->getPreferredThemeRaw());
        self::assertNull($prefs->getPreferredContentWidthRaw());
        self::assertNull($prefs->getPreferredUiDensityRaw());
        self::assertNull($prefs->getPreferredFontScaleRaw());
        self::assertNull($prefs->getPreferredSidebarRaw());
        self::assertSame(UserDisplayPreferenceDefaults::CONTENT_WIDTH, $prefs->getPreferredContentWidth());
        self::assertFalse($prefs->isProductTourSeen());
        self::assertSame([], $prefs->getProductTourSeenPages());
        self::assertFalse($prefs->isPushNotificationsEnabled());
    }

    public function testSettersRejectInvalidEnumsAndApplyDefaults(): void
    {
        $prefs = new UserUiPreferences();
        $prefs->setPreferredLocale('  ES ');
        $prefs->setPreferredTheme('neon');
        $prefs->setPreferredContentWidth('wide');
        $prefs->setPreferredUiDensity('huge');
        $prefs->setPreferredMotion('warp');
        $prefs->setPreferredFontScale('xl');
        $prefs->setPreferredContrast('ultra');
        $prefs->setPreferredSidebar('floating');

        self::assertSame('es', $prefs->getPreferredLocale());
        self::assertSame(UserDisplayPreferenceDefaults::THEME, $prefs->getPreferredTheme());
        self::assertSame(UserDisplayPreferenceDefaults::CONTENT_WIDTH, $prefs->getPreferredContentWidth());
        self::assertSame(UserDisplayPreferenceDefaults::UI_DENSITY, $prefs->getPreferredUiDensity());
        self::assertSame(UserDisplayPreferenceDefaults::MOTION, $prefs->getPreferredMotion());
        self::assertSame(UserDisplayPreferenceDefaults::FONT_SCALE, $prefs->getPreferredFontScale());
        self::assertSame(UserDisplayPreferenceDefaults::CONTRAST, $prefs->getPreferredContrast());
        self::assertSame(UserDisplayPreferenceDefaults::SIDEBAR, $prefs->getPreferredSidebar());

        $prefs->setPreferredTheme('dark');
        $prefs->setPreferredContentWidth('full');
        $prefs->setPreferredUiDensity('compact');
        $prefs->setPreferredMotion('reduce');
        $prefs->setPreferredFontScale('lg');
        $prefs->setPreferredContrast('more');
        $prefs->setPreferredSidebar('collapsed');

        self::assertSame('dark', $prefs->getPreferredTheme());
        self::assertSame('full', $prefs->getPreferredContentWidth());
        self::assertSame('compact', $prefs->getPreferredUiDensity());
        self::assertSame('reduce', $prefs->getPreferredMotion());
        self::assertSame('lg', $prefs->getPreferredFontScale());
        self::assertSame('more', $prefs->getPreferredContrast());
        self::assertSame('collapsed', $prefs->getPreferredSidebar());
    }

    public function testCollapsedPanelsAlwaysNormalizeToEmptyList(): void
    {
        $prefs = new UserUiPreferences();
        $prefs->setPreferredCollapsedIssuePanels(['a', 'b']);
        self::assertSame([], $prefs->getPreferredCollapsedIssuePanels());
        $prefs->setPreferredCollapsedIssuePanels(null);
        self::assertSame([], $prefs->getPreferredCollapsedIssuePanels());
    }

    public function testProductTourPageTracking(): void
    {
        $prefs = new UserUiPreferences();
        self::assertFalse($prefs->hasSeenTourPage('dashboard'));
        self::assertSame(['dashboard', 'admin'], $prefs->getEnabledProductTourPages());

        $prefs->markTourPageSeen('nope');
        self::assertSame([], $prefs->getProductTourSeenPages());

        $prefs->markTourPageSeen('dashboard');
        self::assertTrue($prefs->hasSeenTourPage('dashboard'));
        self::assertFalse($prefs->isProductTourSeen());
        self::assertSame(['admin'], $prefs->getEnabledProductTourPages());

        $prefs->markTourPageSeen('admin');
        self::assertTrue($prefs->isProductTourSeen());
        self::assertSame([], $prefs->getEnabledProductTourPages());
        self::assertTrue($prefs->hasSeenTourPage('dashboard'));

        $prefs->clearProductTourSeen();
        self::assertFalse($prefs->isProductTourSeen());
        self::assertSame([], $prefs->getProductTourSeenPages());

        $prefs->syncEnabledProductTourPages(['dashboard']);
        self::assertSame(['admin'], $prefs->getProductTourSeenPages());
        self::assertFalse($prefs->isProductTourSeen());

        $prefs->syncEnabledProductTourPages(['dashboard', 'admin']);
        self::assertNull($prefs->getProductTourSeenAt());
        self::assertSame([], $prefs->getProductTourSeenPages());

        $prefs->markProductTourSeen();
        self::assertTrue($prefs->isProductTourSeen());
        self::assertSame(['dashboard', 'admin'], $prefs->getProductTourSeenPages());
        self::assertSame([], $prefs->getEnabledProductTourPages());

        $prefs->syncEnabledProductTourPages([]);
        self::assertTrue($prefs->isProductTourSeen());
    }
}
