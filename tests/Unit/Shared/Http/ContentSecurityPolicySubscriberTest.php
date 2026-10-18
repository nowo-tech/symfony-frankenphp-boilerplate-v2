<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Http;

use App\Shared\Http\ContentSecurityPolicySubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ContentSecurityPolicySubscriberTest extends TestCase
{
    public function testProdDefaultCspHasNoUnsafeEval(): void
    {
        $response = $this->dispatch('/', '<html><body>ok</body></html>', kernelDebug: false);
        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringNotContainsString('unsafe-eval', $csp);
        self::assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        self::assertStringContainsString("connect-src 'self' ws: wss:", $csp);
        self::assertStringContainsString("worker-src 'self'", $csp);
    }

    public function testDebugCspAllowsUnsafeEvalForWebDebugToolbar(): void
    {
        $response = $this->dispatch('/', '<html><body>ok</body></html>', kernelDebug: true);
        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringContainsString("'unsafe-eval'", $csp);
        self::assertStringContainsString("connect-src 'self' ws: wss:", $csp);
    }

    public function testProdPathDoesNotAllowUnsafeEvalForFormerSwaggerRoute(): void
    {
        $response = $this->dispatch('/admin/api/doc', '<html><body>doc</body></html>', kernelDebug: false);
        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function testDoesNotOverrideExistingCsp(): void
    {
        $response = new Response('<html></html>', Response::HTTP_OK, [
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
        $kernel = $this->createStub(KernelInterface::class);
        $event = new ResponseEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $response);
        (new ContentSecurityPolicySubscriber(kernelDebug: false))($event);
        self::assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function testSkipsProfilerFragmentPaths(): void
    {
        $response = $this->dispatch('/_wdt/abcdef', '<div class="sf-toolbarreset"></div>', kernelDebug: true);
        self::assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function testStampsNonceOnInlineScriptsWithoutNonce(): void
    {
        $html = '<html><body><script>window.x=1</script><script src="/app.js"></script>'
            .'<script nonce="already">ok()</script>'
            .'<script type="application/json">{"a":1}</script></body></html>';
        $response = $this->dispatch('/', $html, kernelDebug: false);
        $content = (string) $response->getContent();
        $nonce = (string) preg_replace(
            "/.*script-src 'self' 'nonce-([^']+)'.*/s",
            '$1',
            (string) $response->headers->get('Content-Security-Policy'),
        );

        self::assertStringContainsString('<script nonce="'.$nonce.'">window.x=1</script>', $content);
        self::assertStringContainsString('<script src="/app.js"></script>', $content);
        self::assertStringContainsString('<script nonce="already">ok()</script>', $content);
        self::assertStringContainsString('<script type="application/json">{"a":1}</script>', $content);
    }

    public function testStampsNonceOnInlineStylesWithoutNonce(): void
    {
        $html = '<html><head><style>body{margin:0}</style><style nonce="already">.x{}</style></head><body></body></html>';
        $response = $this->dispatch('/', $html, kernelDebug: false);
        $content = (string) $response->getContent();
        $nonce = (string) preg_replace(
            "/.*style-src-elem 'self' 'nonce-([^']+)'.*/s",
            '$1',
            (string) $response->headers->get('Content-Security-Policy'),
        );

        self::assertStringContainsString('<style nonce="'.$nonce.'">body{margin:0}</style>', $content);
        self::assertStringContainsString('<style nonce="already">.x{}</style>', $content);
    }

    public function testProdCspAppendsConfiguredConnectSources(): void
    {
        $response = $this->dispatch(
            '/',
            '<html><body>ok</body></html>',
            kernelDebug: false,
            connectSrcExtra: [
                'https://*.ingest.sentry.io',
                'https://sentry.io',
                '',
            ],
        );
        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("connect-src 'self' ws: wss: https://*.ingest.sentry.io https://sentry.io", $csp);
    }

    public function testProdCspAppendsConfiguredScriptSources(): void
    {
        $response = $this->dispatch(
            '/',
            '<html><body>ok</body></html>',
            kernelDebug: false,
            scriptSrcExtra: [
                'https://cdn.example.test',
                '',
            ],
        );
        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString('https://cdn.example.test', $csp);
        self::assertMatchesRegularExpression("/script-src 'self' 'nonce-[^']+' https:\\/\\/cdn\\.example\\.test/", $csp);
    }

    /**
     * @param list<string> $connectSrcExtra
     * @param list<string> $scriptSrcExtra
     */
    private function dispatch(
        string $path,
        string $html,
        bool $kernelDebug,
        array $connectSrcExtra = [],
        array $scriptSrcExtra = [],
    ): Response {
        $kernel = $this->createStub(KernelInterface::class);
        $request = Request::create($path);
        $subscriber = new ContentSecurityPolicySubscriber($kernelDebug, $connectSrcExtra, $scriptSrcExtra);
        $subscriber(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $response = new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=UTF-8']);
        $subscriber(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        return $response;
    }
}
