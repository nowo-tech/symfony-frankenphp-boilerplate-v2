<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Appearance;

use App\Shared\Appearance\Entity\SiteAppearance;
use App\Shared\Appearance\Repository\SiteAppearanceRepository;
use App\Shared\Appearance\SiteAppearanceProvider;
use PHPUnit\Framework\TestCase;

final class SiteAppearanceProviderTest extends TestCase
{
    public function testDelegatesBrandThemeAndPaletteAccessors(): void
    {
        $appearance = SiteAppearance::defaults();
        $appearance
            ->setBrandName('Shell')
            ->setBrandEyebrow('Platform')
            ->setFooterFixed(true)
            ->setCornerStyle(SiteAppearance::CORNER_SOFT)
            ->setBorderStrength(SiteAppearance::BORDER_STRONG)
            ->setAccentColor('#112233')
            ->setAccentDeepColor('#223344')
            ->setAccentColorDark('#334455')
            ->setAccentDeepColorDark('#445566')
            ->setDangerColor('#556677')
            ->setDangerColorDark('#667788')
            ->setWarnColor('#778899')
            ->setWarnColorDark('#8899aa')
            ->setPaperColor('#99aabb')
            ->setPaperColorDark('#aabbcc')
            ->setInkColor('#bbccdd')
            ->setInkColorDark('#ccddee')
            ->setSurfaceColor('#ddeeff')
            ->setSurfaceColorDark('#eeff00');

        $repo = $this->createMock(SiteAppearanceRepository::class);
        $repo->expects(self::once())->method('getOrCreate')->willReturn($appearance);

        $provider = new SiteAppearanceProvider($repo);

        self::assertSame('Shell', $provider->getBrandName());
        self::assertSame('Platform', $provider->getBrandEyebrow());
        self::assertTrue($provider->isFooterFixed());
        self::assertSame(SiteAppearance::CORNER_SOFT, $provider->getCornerStyle());
        self::assertSame(SiteAppearance::BORDER_STRONG, $provider->getBorderStrength());
        self::assertSame('#112233', $provider->getAccentColor());
        self::assertSame('#223344', $provider->getAccentDeepColor());
        self::assertSame('#334455', $provider->getAccentColorDark());
        self::assertSame('#445566', $provider->getAccentDeepColorDark());
        self::assertSame('#556677', $provider->getDangerColor());
        self::assertSame('#667788', $provider->getDangerColorDark());
        self::assertSame('#778899', $provider->getWarnColor());
        self::assertSame('#8899aa', $provider->getWarnColorDark());
        self::assertSame('#99aabb', $provider->getPaperColor());
        self::assertSame('#aabbcc', $provider->getPaperColorDark());
        self::assertSame('#bbccdd', $provider->getInkColor());
        self::assertSame('#ccddee', $provider->getInkColorDark());
        self::assertSame('#ddeeff', $provider->getSurfaceColor());
        self::assertSame('#eeff00', $provider->getSurfaceColorDark());
        self::assertNotSame('', $provider->getThemeId());
        self::assertNotSame('', $provider->getThemeIdDark());

        $css = $provider->getCssOverrides();
        self::assertStringContainsString('--app-moss: #112233', $css);
        self::assertStringContainsString('--app-shadow: 0 0 0', $css);

        // Invalid hex falls back inside rgbChannels (light theme shadow).
        $appearance->setInkColor('bad');
        $repo2 = $this->createMock(SiteAppearanceRepository::class);
        $repo2->method('getOrCreate')->willReturn($appearance);
        $provider2 = new SiteAppearanceProvider($repo2);
        self::assertStringContainsString('12 21 36', $provider2->getCssOverrides());
        $provider2->reset();
        self::assertSame($appearance, $provider2->refresh());
    }
}
