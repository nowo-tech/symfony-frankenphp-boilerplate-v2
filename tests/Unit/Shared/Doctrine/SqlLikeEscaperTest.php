<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Doctrine;

use App\Shared\Doctrine\SqlLikeEscaper;
use PHPUnit\Framework\TestCase;

final class SqlLikeEscaperTest extends TestCase
{
    public function testEscapesWildcardsAndBackslash(): void
    {
        self::assertSame('foo\\%bar\\_baz\\\\', SqlLikeEscaper::escape('foo%bar_baz\\'));
    }
}
