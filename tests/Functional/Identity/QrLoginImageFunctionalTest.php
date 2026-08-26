<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * AUTH-005: QR phone login is disabled until a real OTP / verification provider is wired.
 */
final class QrLoginImageFunctionalTest extends DatabaseWebTestCase
{
    public function testQrStartIsDisabled(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/login/qr');

        $status = $client->getResponse()->getStatusCode();
        self::assertTrue(
            \in_array($status, [404, 403], true) || $client->getResponse()->isRedirection(),
            sprintf('Expected QR start unavailable (404/403/redirect), got %d.', $status),
        );

        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
            $html = $client->getResponse()->getContent() ?: '';
            self::assertStringNotContainsString('data-controller="qr-login"', $html);
        }
    }
}
