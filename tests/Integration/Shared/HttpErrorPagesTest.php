<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use App\Tests\Support\DatabaseWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Environment;

/**
 * Error preview HTTP routes (/_error/{code}) exist only in APP_ENV=dev.
 * Tests render the Twig overrides directly so CI (test env) still covers them.
 */
final class HttpErrorPagesTest extends DatabaseWebTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: int, 2: string}>
     */
    public static function errorTemplateProvider(): iterable
    {
        foreach ([400, 401, 403, 404, 408, 429, 500, 502, 503] as $code) {
            yield (string) $code => [
                \sprintf('@Twig/Exception/error%d.html.twig', $code),
                $code,
                \sprintf('illustrations/error-%d.png', $code),
            ];
        }
    }

    #[DataProvider('errorTemplateProvider')]
    public function testErrorTemplatesIncludeIllustration(string $template, int $statusCode, string $illustration): void
    {
        self::createClient();
        $html = self::getContainer()->get(Environment::class)->render($template, [
            'status_code' => $statusCode,
            'status_text' => 'Test',
        ]);

        self::assertStringContainsString('error-page', $html);
        self::assertStringContainsString($illustration, $html);
        self::assertStringContainsString('error-page__hint', $html);
    }

    public function testMascotAndErrorAssetsArePublished(): void
    {
        $root = \dirname(__DIR__, 3);
        self::assertFileExists($root.'/public/brand/mascot.png');
        foreach ([400, 401, 403, 404, 408, 429, 500, 502, 503] as $code) {
            self::assertFileExists($root.'/public/illustrations/error-'.$code.'.png');
        }
    }
}
