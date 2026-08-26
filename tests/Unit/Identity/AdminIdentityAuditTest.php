<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity;

use App\Identity\AdminIdentityAudit;
use App\Identity\UserActionType;
use PHPUnit\Framework\TestCase;

final class AdminIdentityAuditTest extends TestCase
{
    public function testUserTimelineActionsAreNonEmptyAllowlist(): void
    {
        $actions = AdminIdentityAudit::userTimelineActions();
        self::assertNotEmpty($actions);
        self::assertContains(UserActionType::UserCreated, $actions);
        self::assertContains(UserActionType::UserAnonymized, $actions);
        self::assertSame(100, AdminIdentityAudit::TIMELINE_LIMIT);
    }

    public function testGroupTimelineActionsAreNonEmptyAllowlist(): void
    {
        $actions = AdminIdentityAudit::groupTimelineActions();
        self::assertNotEmpty($actions);
        self::assertContains(UserActionType::GroupCreated, $actions);
        self::assertContains(UserActionType::GroupMemberAdded, $actions);
    }
}
