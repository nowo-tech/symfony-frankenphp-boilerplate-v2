<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Appearance;

use App\Identity\Entity\User;
use App\Shared\Appearance\Entity\SiteAppearance;
use PHPUnit\Framework\TestCase;

final class SiteAppearanceEntityTest extends TestCase
{
    public function testResetToDefaultsRestoresCanonicalPalette(): void
    {
        $appearance = SiteAppearance::defaults();
        $appearance
            ->setBrandName('Custom')
            ->setBrandEyebrow('Eyebrow')
            ->setAccentColor('#abcdef')
            ->setCornerStyle('not-a-style')
            ->setBorderStrength('not-a-strength')
            ->setFooterFixed(true);

        self::assertSame(SiteAppearance::CORNER_SOFT, $appearance->getCornerStyle());
        self::assertSame(SiteAppearance::BORDER_MEDIUM, $appearance->getBorderStrength());

        $appearance->resetToDefaults();

        self::assertSame(SiteAppearance::DEFAULT_BRAND_NAME, $appearance->getBrandName());
        self::assertSame(SiteAppearance::DEFAULT_BRAND_EYEBROW, $appearance->getBrandEyebrow());
        self::assertSame(SiteAppearance::DEFAULT_ACCENT, $appearance->getAccentColor());
        self::assertSame(SiteAppearance::CORNER_SHARP, $appearance->getCornerStyle());
        self::assertSame(SiteAppearance::BORDER_MEDIUM, $appearance->getBorderStrength());
        self::assertFalse($appearance->isFooterFixed());
        self::assertSame('shell', $appearance->getThemeId());
        self::assertSame('custom', $appearance->getThemeIdDark());
    }

    public function testAuditBlameOnlyAcceptsUserEntities(): void
    {
        $appearance = SiteAppearance::defaults();
        $user = new User();
        $user->setEmail('a@example.com');
        $user->setDisplayName('A');

        $appearance->setCreatedBy($user);
        $appearance->setUpdatedBy(new \stdClass());
        self::assertSame($user, $appearance->getCreatedBy());
        self::assertNull($appearance->getUpdatedBy());
        self::assertSame(1, $appearance->getId());
    }
}
