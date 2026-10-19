<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * shared/_table_pagination.html.twig (product tables) delegates to the UiKit `pagination` theme partial
 * layouts/partials/_pagination.html.twig — it used to include the deleted kit/_pagination.html.twig — and translates
 * showing / previous / next in the NowoUiKitBundle domain.
 */
final class TablePaginationTemplateTest extends KernelTestCase
{
    public function testProductTablePaginationRendersTheThemePartial(): void
    {
        self::bootKernel();
        // The partial falls back to the current request's route params when the caller passes none.
        $requestStack = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push(Request::create('/admin/groups'));
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->render('shared/_table_pagination.html.twig', [
            'pagination' => ['page' => 2, 'per_page' => 10, 'total' => 35, 'total_pages' => 4],
            'item_count' => 10,
            'route' => 'admin_groups',
        ]);
        $crawler = new Crawler($html);

        self::assertCount(1, $crawler->filter('.table-pagination'));
        self::assertStringContainsString('11', $crawler->filter('.table-pagination__info')->text());
        self::assertStringContainsString('35', $crawler->filter('.table-pagination__info')->text());
        self::assertStringNotContainsString('pagination.', $html);
        self::assertCount(1, $crawler->filter('.page-item.active'));
        self::assertStringContainsString('page=3', (string) $crawler->filter('a[aria-label]')->last()->attr('href'));
    }
}
