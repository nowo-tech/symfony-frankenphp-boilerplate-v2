<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared;

use App\Tests\Support\DatabaseWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * UiKit 2 page contract (REQ-UI-003): kit pages render through the app bridges (nowo_ui_kit.layouts.admin /
 * guest = templates/layouts/ui_admin.html.twig / ui_guest.html.twig), with the app tokens and nonce'd UiKit scripts.
 */
final class UiPageContractTest extends DatabaseWebTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function kitAdminPages(): iterable
    {
        yield 'dashboard menu' => ['/admin/menus/', 'NowoDashboardMenuBundle'];
        yield 'breadcrumb kit' => ['/breadcrumb-kit-admin/', 'NowoBreadcrumbKitBundle'];
        yield 'routing kit' => ['/admin/_routing/', 'NowoRoutingKitBundle'];
        yield 'cookie consent' => ['/admin/cookie-consent', 'NowoCookieConsentBundle'];
        yield 'maintenance' => ['/admin/maintenance/', 'NowoMaintenanceModeBundle'];
    }

    #[DataProvider('kitAdminPages')]
    public function testKitAdminPageRendersInTheAdminBridge(string $path, string $bundle): void
    {
        [$client, $admin] = $this->bootWithDemoProject('ui-contract-admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        self::getContainer()->get('doctrine')->getManager()->flush();
        $this->login($client, $admin);

        $crawler = $client->request(Request::METHOD_GET, $path);
        // Entry routes redirect to the kit's first page (e.g. /admin/cookie-consent → config settings).
        for ($hops = 0; $client->getResponse()->isRedirect() && $hops < 3; ++$hops) {
            $crawler = $client->followRedirect();
        }

        self::assertResponseIsSuccessful();
        // Signed-in app shell (sidebar) around the kit scaffold.
        self::assertSelectorExists('#app-sidebar');
        self::assertCount(1, $crawler->filter(\sprintf('.kit-admin.nowo-ui-host-page[data-nowo-ui-bundle="%s"]', $bundle)));
        self::assertSelectorExists('html[data-nowo-ui-host-tokens]');
        self::assertSelectorExists('.kit-admin-page-header__title');
        // UiKit runtimes come from the scripts partial with the CSP nonce.
        self::assertGreaterThan(0, $crawler->filter('script[src*="nowo-ui-"][nonce]')->count());
        self::assertCount(0, $crawler->filter('script[src*="nowo-ui-"]:not([nonce])'));
    }

    public function testLoginIsTheAuthScaffoldInTheGuestBridge(): void
    {
        $client = self::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.nowo-ui-host-page--auth[data-nowo-ui-bundle="NowoAuthKitBundle"] section.panel[data-nowo-ui-page="auth"]'));
        self::assertSelectorExists('[data-testid="auth-kit-login"] button[type="submit"].btn-primary');
        self::assertSelectorExists('.brand-mark');
        self::assertSelectorExists('html[data-nowo-ui-host-tokens]');
        self::assertCount(0, $crawler->filter('script[src*="nowo-ui-"]:not([nonce])'));
    }

    public function testMaintenancePreviewRendersWithoutSessionChrome(): void
    {
        $client = self::createClient();
        $crawler = $client->request(Request::METHOD_GET, '/_maintenance_preview');

        self::assertContains($client->getResponse()->getStatusCode(), [200, 503]);
        self::assertSelectorExists('.maintenance-page__figure img[src*="error-503"]');
        self::assertSelectorExists('.maintenance-page__thanks');
        // Public 503 renders before the firewall: no locale switcher (session) and no cookie consent modal.
        self::assertCount(0, $crawler->filter('#cookieconsent'));
        self::assertCount(0, $crawler->filter('.top-controls form'));
    }
}
