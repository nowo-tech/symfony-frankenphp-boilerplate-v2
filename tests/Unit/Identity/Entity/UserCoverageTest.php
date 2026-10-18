<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Entity;

use App\Identity\Entity\InstanceRole;
use App\Identity\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

final class UserCoverageTest extends TestCase
{
    public function testInitialsFallBackToEmailWhenDisplayNameEmpty(): void
    {
        $user = new User();
        $user->setEmail('ab@example.com');
        $user->setDisplayName('  ');

        self::assertSame('AB', $user->getInitials());
    }

    public function testGetRolesMergesEnabledInstanceRolesOnly(): void
    {
        $user = new User();
        $user->setEmail('u@example.com');
        $user->setDisplayName('U');
        $user->setRoles(['ROLE_ADMIN']);

        $enabled = new InstanceRole();
        $enabled->setCode('ROLE_SUPPORT');
        $enabled->setEnabled(true);
        $disabled = new InstanceRole();
        $disabled->setCode('ROLE_DISABLED');
        $disabled->setEnabled(false);

        $user->addInstanceRole($enabled);
        $user->addInstanceRole($enabled);
        $user->addInstanceRole($disabled);

        $roles = $user->getRoles();
        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_SUPPORT', $roles);
        self::assertNotContains('ROLE_DISABLED', $roles);
        self::assertTrue($user->hasInstanceRole($enabled));
        $user->removeInstanceRole($enabled);
        self::assertFalse($user->hasInstanceRole($enabled));
    }

    public function testPlainPasswordAndPreferenceDelegates(): void
    {
        $user = new User();
        $user->setEmail('u@example.com');
        $user->setDisplayName('U');

        $user->setPlainPassword('secret');
        self::assertSame('secret', $user->getPlainPassword());
        $user->clearPlainPassword();
        self::assertNull($user->getPlainPassword());

        $user->setPreferredUiDensity('compact');
        self::assertSame('compact', $user->getPreferredUiDensity());
        self::assertSame('compact', $user->getPreferredUiDensityRaw());

        $user->setPreferredFontScale('lg');
        self::assertSame('lg', $user->getPreferredFontScaleRaw());

        $user->setPreferredSidebar('collapsed');
        self::assertSame('collapsed', $user->getPreferredSidebarRaw());

        $user->setPreferredCollapsedIssuePanels(null);
        self::assertSame([], $user->getPreferredCollapsedIssuePanels());

        self::assertFalse($user->isProductTourSeen());
        $user->markProductTourSeen(new DateTimeImmutable('2024-01-01'));
        self::assertTrue($user->isProductTourSeen());
        self::assertNotNull($user->getProductTourSeenAt());
        $user->clearProductTourSeen();
        self::assertFalse($user->isProductTourSeen());

        $user->markTourPageSeen('dashboard');
        self::assertTrue($user->hasSeenTourPage('dashboard'));
        self::assertContains('dashboard', $user->getProductTourSeenPages());

        $user->syncEnabledProductTourPages(['dashboard']);
        self::assertSame(['dashboard'], $user->getEnabledProductTourPages());

        $other = new User();
        $user->setCreatedBy($other);
        $user->setUpdatedBy(new stdClass());
        self::assertSame($other, $user->getCreatedBy());
        self::assertNull($user->getUpdatedBy());
    }
}
