<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class PwaEndpointsTest extends WebTestCase
{
    public function testManifestIsPublic(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/manifest.webmanifest');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/manifest+json');
        $payload = json_decode($client->getResponse()->getContent() ?: '', true);
        self::assertIsArray($payload);
        self::assertSame('Nowo Shell', $payload['name'] ?? null);
        self::assertSame('Shell', $payload['short_name'] ?? null);
    }

    public function testServiceWorkerIsPublic(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/sw.js');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('javascript', $client->getResponse()->headers->get('content-type') ?? '');
    }

    public function testOfflinePageIsPublic(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/offline');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('h1');
    }
}
