<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the baseline Content-Security-Policy on HTML responses.
 *
 * Lives in PHP (not Caddy) so Symfony WebProfiler can merge its script/style
 * nonces into the same header. Caddy still sets the other hardening headers.
 *
 * A per-request nonce is issued on the main request ({@see REQUEST_ATTR_NONCE}) for
 * host {@code <style>} blocks. Production uses {@code style-src-elem 'self' 'nonce-…'}
 * (no {@code unsafe-inline} on elements). {@code style-src-attr 'unsafe-inline'} allows
 * CSSOM ({@code element.style}) used by SortableJS, sidebar motion, etc.
 *
 * Debug keeps {@code unsafe-inline} on {@code style-src-elem} for the Web Profiler.
 *
 * The response body is never rewritten: templates print `nonce="{{ csp_nonce() }}"` themselves (the kits read the
 * same nonce from the `csp_nonce` request attribute). Stamping every bare inline {@code <script>} / {@code <style>}
 * would also bless injected markup and void the nonce.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 1024)]
#[AsEventListener(event: KernelEvents::RESPONSE, priority: -100)]
final readonly class ContentSecurityPolicySubscriber
{
    public const string REQUEST_ATTR_NONCE = '_shell_csp_nonce';

    /** Request attribute the nowo kits (UiKit scripts partial, AuthKit, MaintenanceMode) read the nonce from. */
    public const string KIT_REQUEST_ATTR_NONCE = 'csp_nonce';

    private const string CSP_BASE = "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob:; font-src 'self' data:; ";

    /**
     * @param list<string> $connectSrcExtra
     * @param list<string> $scriptSrcExtra
     */
    public function __construct(
        #[Autowire(param: 'kernel.debug')]
        private bool $kernelDebug,
        #[Autowire(param: 'app.csp.connect_src_extra')]
        private array $connectSrcExtra = [],
        #[Autowire(param: 'app.csp.script_src_extra')]
        private array $scriptSrcExtra = [],
    ) {
    }

    public function __invoke(RequestEvent|ResponseEvent $event): void
    {
        if ($event instanceof RequestEvent) {
            $this->onRequest($event);

            return;
        }

        $this->onResponse($event);
    }

    private function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has(self::REQUEST_ATTR_NONCE)) {
            $request->attributes->set(self::REQUEST_ATTR_NONCE, base64_encode(random_bytes(16)));
        }
        // UiKit / AuthKit / MaintenanceMode templates read the nonce from the `csp_nonce` request attribute.
        if (!$request->attributes->has(self::KIT_REQUEST_ATTR_NONCE)) {
            $request->attributes->set(self::KIT_REQUEST_ATTR_NONCE, $request->attributes->get(self::REQUEST_ATTR_NONCE));
        }
    }

    private function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if ($this->isProfilerFragmentPath($path)) {
            return;
        }

        $response = $event->getResponse();
        if ($response->headers->has('Content-Security-Policy')) {
            return;
        }

        if (!$this->isHtmlResponse($response)) {
            return;
        }

        $response->headers->set('Content-Security-Policy', $this->buildCsp($request));
    }

    private function buildCsp(Request $request): string
    {
        $nonce = (string) $request->attributes->get(self::REQUEST_ATTR_NONCE, '');
        $styleSrcElem = "style-src-elem 'self'";
        if ('' !== $nonce) {
            $styleSrcElem .= " 'nonce-".$nonce."'";
        }
        if ($this->kernelDebug) {
            $styleSrcElem .= " 'unsafe-inline'";
        }

        $styleSrcAttr = "style-src-attr 'unsafe-inline'";

        $scriptSrc = "script-src 'self'";
        if ('' !== $nonce) {
            $scriptSrc .= " 'nonce-".$nonce."'";
        }
        if ($this->kernelDebug) {
            $scriptSrc .= " 'unsafe-eval'";
        }

        foreach ($this->scriptSrcExtra as $source) {
            $source = trim((string) $source);
            if ('' !== $source) {
                $scriptSrc .= ' '.$source;
            }
        }

        $connectSrc = "connect-src 'self' ws: wss:";
        foreach ($this->connectSrcExtra as $source) {
            $source = trim((string) $source);
            if ('' !== $source) {
                $connectSrc .= ' '.$source;
            }
        }

        return self::CSP_BASE.$styleSrcElem.'; '.$styleSrcAttr.'; '.$scriptSrc.'; '.$connectSrc."; worker-src 'self'; manifest-src 'self'";
    }

    private function isHtmlResponse(Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html')) {
            return true;
        }

        $content = $response->getContent();

        return \is_string($content) && str_contains(substr($content, 0, 512), '<html');
    }

    private function isProfilerFragmentPath(string $path): bool
    {
        return str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler');
    }
}
