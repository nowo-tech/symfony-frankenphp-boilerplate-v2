<?php

declare(strict_types=1);

namespace App\Shared\Twig;

use App\Shared\Http\ContentSecurityPolicySubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Attribute\AsTwigFunction;

/**
 * Exposes the per-request CSP nonce for host {@code <style>} blocks.
 */
final readonly class CspNonceTwigExtension
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    #[AsTwigFunction(name: 'csp_nonce')]
    public function nonce(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return '';
        }

        return (string) $request->attributes->get(ContentSecurityPolicySubscriber::REQUEST_ATTR_NONCE, '');
    }
}
