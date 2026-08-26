<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Settings;

use App\Shared\Appearance\AppearanceThemePresets;
use App\Shared\Appearance\Entity\SiteAppearance;
use App\Shared\Appearance\Repository\SiteAppearanceRepository;
use App\Shared\Appearance\SiteAppearanceProvider;
use App\Shared\Settings\Entity\InstanceSettings;
use App\Shared\Settings\Repository\InstanceSettingsRepository;
use App\Shared\Settings\Service\InstanceConfigPortability;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InstanceConfigPortabilityTest extends TestCase
{
    private SiteAppearanceRepository&MockObject $appearanceRepo;
    private InstanceSettingsRepository&MockObject $settingsRepo;
    private SiteAppearanceProvider $provider;

    protected function setUp(): void
    {
        $this->appearanceRepo = $this->createMock(SiteAppearanceRepository::class);
        $this->settingsRepo = $this->createMock(InstanceSettingsRepository::class);
        $this->provider = new SiteAppearanceProvider($this->appearanceRepo);
    }

    public function testExportIncludesSchemaAndNonSecretFlags(): void
    {
        $appearance = new SiteAppearance();
        $appearance->setBrandName('Acme');
        $settings = new InstanceSettings();
        $settings->markSetupCompleted();

        $this->appearanceRepo->method('getOrCreate')->willReturn($appearance);
        $this->settingsRepo->method('getOrCreate')->willReturn($settings);

        $payload = $this->sut()->export();

        self::assertSame(InstanceConfigPortability::SCHEMA, $payload['schema']);
        self::assertSame(InstanceConfigPortability::VERSION, $payload['version']);
        self::assertSame('Acme', $payload['appearance']['brand_name']);
        self::assertTrue($payload['instance']['setup_completed']);
        self::assertNotNull($payload['instance']['setup_completed_at']);
        self::assertFalse($payload['instance']['mailer_configured']);
    }

    public function testImportRejectsInvalidSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_schema');
        $this->sut()->import(['schema' => 'other', 'version' => 1, 'appearance' => ['brand_name' => 'X']]);
    }

    public function testImportRejectsUnsupportedVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported_version');
        $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 99,
            'appearance' => ['brand_name' => 'X'],
        ]);
    }

    public function testImportRejectsForbiddenNestedKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('forbidden_key:mailer_dsn');
        $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'instance' => ['mailer_dsn' => 'smtp://secret'],
        ]);
    }

    public function testImportRejectsEmptyPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty_payload');
        $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
        ]);
    }

    public function testImportAppliesAppearanceColorsAndClearsSetup(): void
    {
        $appearance = new SiteAppearance();
        $settings = new InstanceSettings();
        $settings->markSetupCompleted();

        $this->appearanceRepo->method('getOrCreate')->willReturn($appearance);
        $this->appearanceRepo->expects(self::once())->method('save')->with($appearance);
        $this->settingsRepo->method('getOrCreate')->willReturn($settings);
        $this->settingsRepo->expects(self::once())->method('save')->with($settings);

        $applied = $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'appearance' => [
                'brand_name' => 'Imported',
                'brand_eyebrow' => 'Eyebrow',
                'footer_fixed' => '1',
                'corner_style' => SiteAppearance::CORNER_SOFT,
                'border_strength' => SiteAppearance::BORDER_MEDIUM,
                'accent_color' => '#abcdef',
            ],
            'instance' => [
                'setup_completed' => false,
                'mailer_configured' => true,
            ],
        ]);

        self::assertSame(['appearance', 'instance'], $applied);
        self::assertSame('Imported', $appearance->getBrandName());
        self::assertSame('Eyebrow', $appearance->getBrandEyebrow());
        self::assertTrue($appearance->isFooterFixed());
        self::assertSame(SiteAppearance::CORNER_SOFT, $appearance->getCornerStyle());
        self::assertSame(SiteAppearance::BORDER_MEDIUM, $appearance->getBorderStrength());
        self::assertSame('#abcdef', $appearance->getAccentColor());
        self::assertFalse($settings->isSetupCompleted());
        // refresh() reloads from repository
        self::assertSame($appearance, $this->provider->get());
    }

    public function testImportAppliesNamedThemeEarlyReturn(): void
    {
        $appearance = new SiteAppearance();
        $this->appearanceRepo->method('getOrCreate')->willReturn($appearance);
        $this->appearanceRepo->expects(self::once())->method('save')->with($appearance);

        $applied = $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'appearance' => [
                'theme_id' => AppearanceThemePresets::SHELL,
                'theme_id_dark' => 'midnight',
                'accent_color' => '#ffffff',
            ],
        ]);

        self::assertSame(['appearance'], $applied);
        self::assertSame($appearance, $this->provider->get());
    }

    public function testImportMarksSetupCompletedWhenMissing(): void
    {
        $settings = new InstanceSettings();
        $this->settingsRepo->method('getOrCreate')->willReturn($settings);
        $this->settingsRepo->expects(self::once())->method('save')->with($settings);

        $applied = $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'instance' => ['setup_completed' => true],
        ]);

        self::assertSame(['instance'], $applied);
        self::assertTrue($settings->isSetupCompleted());
    }

    public function testImportDoesNotRemarqueAlreadyCompletedSetup(): void
    {
        $settings = new InstanceSettings();
        $settings->markSetupCompleted();
        $completedAt = $settings->getSetupCompletedAt();

        $this->settingsRepo->method('getOrCreate')->willReturn($settings);
        $this->settingsRepo->expects(self::once())->method('save')->with($settings);

        $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'instance' => ['setup_completed' => true],
        ]);

        self::assertSame($completedAt, $settings->getSetupCompletedAt());
    }

    public function testImportRejectsInvalidColor(): void
    {
        $this->appearanceRepo->method('getOrCreate')->willReturn(new SiteAppearance());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_color:accent_color');
        $this->sut()->import([
            'schema' => InstanceConfigPortability::SCHEMA,
            'version' => 1,
            'appearance' => ['accent_color' => 'not-a-hex'],
        ]);
    }

    private function sut(): InstanceConfigPortability
    {
        return new InstanceConfigPortability($this->appearanceRepo, $this->settingsRepo, $this->provider);
    }
}
