<?php

declare(strict_types=1);

namespace App\Tests\Functional\Shared;

use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class HealthEndpointsTest extends DatabaseWebTestCase
{
    public function testLiveAndReadyArePublic(): void
    {
        // Ensures schema exists (DatabaseWebTestCase recreates it per boot).
        [$client] = $this->bootWithDemoProject('health@example.com');

        $client->request(Request::METHOD_GET, '/health/live');
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertIsArray($payload['runtime'] ?? null);
        self::assertContains($payload['runtime']['frankenphp_mode'] ?? null, ['classic', 'worker']);
        self::assertArrayHasKey('frankenphp_worker', $payload['runtime'] ?? []);
        self::assertArrayHasKey('reset_kernel', $payload['runtime'] ?? []);
        self::assertArrayHasKey('app_runtime_mode', $payload['runtime'] ?? []);
        self::assertArrayHasKey('worker_num', $payload['runtime'] ?? []);

        $client->request(Request::METHOD_GET, '/health/ready');
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '[]', true);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertTrue($payload['checks']['database'] ?? false);
    }
}
