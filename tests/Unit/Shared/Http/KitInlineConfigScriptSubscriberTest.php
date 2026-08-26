<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\KitInlineConfigScriptSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class KitInlineConfigScriptSubscriberTest extends TestCase
{
    public function testRewritesDashboardMenuConfigScript(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
window.__nowoDashboardMenuConfig = Object.assign(window.__nowoDashboardMenuConfig || {}, {
        dashboardBase: "/menus",
        menuId: 3,
        debug: true
    });
</script>
</body></html>
HTML;

        $response = $this->dispatch($html);
        $content = (string) $response->getContent();

        self::assertStringNotContainsString('window.__nowoDashboardMenuConfig = Object.assign', $content);
        self::assertStringContainsString('class="shell-kit-page-config" data-kit="dashboard-menu"', $content);
        self::assertStringContainsString('"dashboardBase":"/menus"', $content);
        self::assertStringContainsString('"menuId":3', $content);
        self::assertStringContainsString('"debug":true', $content);
    }

    public function testRewritesBreadcrumbPageFlag(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
window.__breadcrumbKitDashboard = Object.assign({}, window.__breadcrumbKitDashboard || {}, { page: 'items_index' });
</script>
</body></html>
HTML;

        $content = (string) $this->dispatch($html)->getContent();
        self::assertStringContainsString('data-kit="breadcrumb-kit"', $content);
        self::assertStringContainsString('"page":"items_index"', $content);
        self::assertStringNotContainsString('window.__breadcrumbKitDashboard = Object.assign', $content);
    }

    public function testStripsBreadcrumbInlineEditIife(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
    (function () {
      var wrap = document.querySelector('[data-breadcrumb-kit-inline-wrap="1"]');
      if (!wrap) return;
      var openBtn = wrap.querySelector('[data-bk-inline-open]');
      var dlg = wrap.querySelector('[data-bk-inline-dialog]');
      var closeBtn = wrap.querySelector('[data-bk-inline-close]');
      if (!openBtn || !dlg) return;
      openBtn.addEventListener('click', function () { if (dlg.showModal) dlg.showModal(); });
      closeBtn && closeBtn.addEventListener('click', function () { dlg.close(); });
    })();
  </script>
</body></html>
HTML;

        $content = (string) $this->dispatch($html)->getContent();
        self::assertStringNotContainsString('data-breadcrumb-kit-inline-wrap', $content);
        self::assertStringNotContainsString('<script>', $content);
    }

    public function testRewritesBreadcrumbLayoutAssignments(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
window.__breadcrumbKitDashboard = window.__breadcrumbKitDashboard || {};
window.__breadcrumbKitDashboard.cssFramework = 'bootstrap5';
window.__breadcrumbKitDashboard.importPartialUrl = '/partial';
window.__breadcrumbKitDashboard.dashboardBase = '/dash';
</script>
</body></html>
HTML;

        $content = (string) $this->dispatch($html)->getContent();
        self::assertStringContainsString('data-kit="breadcrumb-kit"', $content);
        self::assertStringContainsString('"cssFramework":"bootstrap5"', $content);
        self::assertStringContainsString('"importPartialUrl":"/partial"', $content);
        self::assertStringContainsString('"dashboardBase":"/dash"', $content);
    }

    public function testSkipsNonHtmlContentTypeEvenWithMarkers(): void
    {
        $html = '<script>window.__nowoDashboardMenuConfig = Object.assign(window.__nowoDashboardMenuConfig || {}, { menuId: 1 });</script>';
        $response = $this->dispatch($html, contentType: 'application/json');
        self::assertSame($html, $response->getContent());
    }

    public function testSkipsSubRequests(): void
    {
        $html = '<script>window.__nowoDashboardMenuConfig = Object.assign(window.__nowoDashboardMenuConfig || {}, { menuId: 1 });</script>';
        $kernel = $this->createStub(KernelInterface::class);
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);
        $event = new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::SUB_REQUEST, $response);
        (new KitInlineConfigScriptSubscriber())($event);
        self::assertSame($html, $response->getContent());
    }

    public function testEmptyContentIsIgnored(): void
    {
        $response = $this->dispatch('');
        self::assertSame('', $response->getContent());
    }

    public function testDecodeJsValueBranchesAndMalformedObjectLiteral(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
window.__breadcrumbKitDashboard = window.__breadcrumbKitDashboard || {};
window.__breadcrumbKitDashboard.cssFramework = true;
window.__breadcrumbKitDashboard.importPartialUrl = false;
window.__breadcrumbKitDashboard.dashboardBase = 1.5;
</script>
<script>
window.__breadcrumbKitDashboard = window.__breadcrumbKitDashboard || {};
window.__breadcrumbKitDashboard.cssFramework = 2;
window.__breadcrumbKitDashboard.importPartialUrl = {"a":1};
window.__breadcrumbKitDashboard.dashboardBase = bareWord;
</script>
<script>
window.__nowoDashboardMenuConfig = Object.assign(window.__nowoDashboardMenuConfig || {}, { broken: function () {} });
</script>
</body></html>
HTML;

        $content = (string) $this->dispatch($html)->getContent();
        self::assertStringContainsString('"cssFramework":true', $content);
        self::assertStringContainsString('"importPartialUrl":false', $content);
        self::assertStringContainsString('"dashboardBase":1.5', $content);
        self::assertStringContainsString('"cssFramework":2', $content);
        self::assertStringContainsString('"importPartialUrl":{"a":1}', $content);
        self::assertStringContainsString('"dashboardBase":"bareWord"', $content);
        self::assertStringContainsString('data-kit="dashboard-menu">{}</script>', $content);
    }

    public function testJsonEncodeFailureKeepsOriginalBreadcrumbScript(): void
    {
        $html = <<<'HTML'
<html><body>
<script>
window.__breadcrumbKitDashboard = window.__breadcrumbKitDashboard || {};
window.__breadcrumbKitDashboard.dashboardBase = 1.0e309;
</script>
</body></html>
HTML;

        $content = (string) $this->dispatch($html)->getContent();
        self::assertStringContainsString('window.__breadcrumbKitDashboard.dashboardBase = 1.0e309', $content);
    }

    #[DataProvider('untouchedProvider')]
    public function testLeavesUnrelatedHtmlAlone(string $html): void
    {
        self::assertSame($html, $this->dispatch($html)->getContent());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function untouchedProvider(): iterable
    {
        yield 'no kit markers' => ['<html><body><script src="/app.js"></script></body></html>'];
        yield 'json island already' => ['<html><body><script type="application/json" id="x">{}</script></body></html>'];
    }

    private function dispatch(string $html, string $contentType = 'text/html; charset=UTF-8'): Response
    {
        $kernel = $this->createStub(KernelInterface::class);
        $request = Request::create('/');
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => $contentType]);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        (new KitInlineConfigScriptSubscriber())($event);

        return $response;
    }
}
