<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Health\HealthController;
use App\Shared\Health\MessengerQueueHealth;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class HealthControllerTest extends TestCase
{
    public function testLiveExposesRuntimeSnapshot(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $controller = new HealthController($em, new MessengerQueueHealth($em), new NullLogger(), 'redis://redis-8.10.2:6379/0');

        $previousServer = $_SERVER;
        $_SERVER['FRANKENPHP_MODE'] = 'worker';
        $_SERVER['FRANKENPHP_WORKER'] = '1';
        $_SERVER['FRANKENPHP_RESET_KERNEL'] = 'false';
        $_SERVER['APP_RUNTIME_MODE'] = 'web=1&worker=1';
        $_SERVER['FRANKENPHP_WORKER_NUM'] = '1';

        try {
            $response = $controller->live();
        } finally {
            $_SERVER = $previousServer;
        }

        $payload = json_decode($response->getContent() ?: '[]', true);
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status'] ?? null);
        self::assertSame('worker', $payload['runtime']['frankenphp_mode'] ?? null);
        self::assertTrue($payload['runtime']['frankenphp_worker'] ?? false);
        self::assertFalse($payload['runtime']['reset_kernel'] ?? true);
        self::assertSame('web=1&worker=1', $payload['runtime']['app_runtime_mode'] ?? null);
        self::assertSame(1, $payload['runtime']['worker_num'] ?? null);
    }

    public function testReadyDoesNotEchoExceptionMessage(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willThrowException(
            new RuntimeException('SQLSTATE secret-db-host password=leaked'),
        );

        $controller = new HealthController($em, new MessengerQueueHealth($em), new NullLogger(), 'redis://redis-8.10.2:6379/0');

        $response = $controller->ready();
        $payload = json_decode($response->getContent() ?: '[]', true);

        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertSame('error', $payload['status'] ?? null);
        self::assertSame('unavailable', $payload['error'] ?? null);
        self::assertStringNotContainsString('leaked', $response->getContent() ?: '');
        self::assertStringNotContainsString('SQLSTATE', $response->getContent() ?: '');
    }
}
