<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Settings;

use App\Identity\Entity\User;
use App\Shared\Settings\Entity\InstanceSettings;
use PHPUnit\Framework\TestCase;
use stdClass;

final class InstanceSettingsEntityTest extends TestCase
{
    public function testMailerMaskingAndSetupMarkers(): void
    {
        $settings = InstanceSettings::defaults();
        self::assertSame(1, $settings->getId());

        $settings->setMailerDsn(null);
        self::assertNull($settings->maskedMailerDsn());
        $settings->setMailerDsn('   ');
        self::assertFalse($settings->hasMailerDsn());

        $settings->setMailerDsn('noscheme');
        self::assertSame(str_repeat('•', 8), $settings->maskedMailerDsn());

        $settings->setMailerDsn('smtp://');
        self::assertSame('smtp://••••', $settings->maskedMailerDsn());

        $settings->setMailerFrom(null);
        self::assertSame(InstanceSettings::DEFAULT_MAILER_FROM, $settings->getEffectiveMailerFrom());
        $settings->setMailerFrom('  ');
        self::assertNull($settings->getMailerFrom());

        $settings->markSetupCompleted();
        self::assertTrue($settings->isSetupCompleted());
        $settings->clearSetupCompleted();
        self::assertFalse($settings->isSetupCompleted());

        $user = new User();
        $user->setEmail('a@example.com');
        $user->setDisplayName('A');
        $settings->setCreatedBy($user);
        $settings->setUpdatedBy(new stdClass());
        self::assertSame($user, $settings->getCreatedBy());
        self::assertNull($settings->getUpdatedBy());
    }
}
