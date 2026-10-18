<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity;

use App\Identity\AdminAuditFilter;
use App\Identity\AdminIdentityAudit;
use App\Identity\UserActionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AdminAuditFilterTest extends TestCase
{
    public function testFromRequestParsesValidFilters(): void
    {
        $request = Request::create('/', Request::METHOD_GET, [
            'action' => UserActionType::UserCreated->value,
            'from' => '2024-01-02',
            'to' => '2024-01-03',
        ]);

        $result = AdminAuditFilter::fromRequest($request, AdminIdentityAudit::userTimelineActions());

        self::assertSame(UserActionType::UserCreated, $result['action']);
        self::assertSame('2024-01-02 00:00:00', $result['from']?->format('Y-m-d H:i:s'));
        self::assertSame('2024-01-03 23:59:59', $result['to']?->format('Y-m-d H:i:s'));
        self::assertSame(UserActionType::UserCreated->value, $result['filter']['action']);
        self::assertSame('2024-01-02', $result['filter']['from']);
        self::assertSame('2024-01-03', $result['filter']['to']);
    }

    public function testFromRequestIgnoresUnknownActionAndBadDates(): void
    {
        $request = Request::create('/', Request::METHOD_GET, [
            'action' => 'not-allowed',
            'from' => 'not-a-date',
            'to' => '2024-99-99',
        ]);

        $result = AdminAuditFilter::fromRequest($request, AdminIdentityAudit::userTimelineActions());

        self::assertNull($result['action']);
        self::assertNull($result['from']);
        self::assertNull($result['to']);
        self::assertSame('', $result['filter']['action']);
        self::assertSame('not-a-date', $result['filter']['from']);
    }

    public function testFromRequestEmptyQuery(): void
    {
        $result = AdminAuditFilter::fromRequest(Request::create('/'), []);

        self::assertNull($result['action']);
        self::assertNull($result['from']);
        self::assertNull($result['to']);
        self::assertSame(['action' => '', 'from' => '', 'to' => ''], $result['filter']);
    }
}
