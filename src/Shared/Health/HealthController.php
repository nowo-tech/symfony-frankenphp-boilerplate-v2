<?php

declare(strict_types=1);

namespace App\Shared\Health;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Liveness and readiness probes for orchestrators (no auth).
 */
#[AsController]
final readonly class HealthController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessengerQueueHealth $messengerQueueHealth,
        private LoggerInterface $logger,
        #[Autowire(env: 'REDIS_URL')]
        private string $redisUrl,
    ) {
    }

    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'runtime' => FrankenPhpRuntime::snapshot(),
        ]);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => false,
            'redis' => false,
            'messenger_async_pending' => null,
            'messenger_failed_pending' => null,
        ];

        try {
            $connection = $this->entityManager->getConnection();
            $connection->executeQuery('SELECT 1');
            $checks['database'] = true;
            $queue = $this->messengerQueueHealth->asyncPending();
            $checks['messenger_async_pending'] = $queue['pending'];
            $checks['messenger_failed_pending'] = $queue['failed'];
        } catch (Throwable $e) {
            $this->logger->error('Readiness probe failed (database/messenger).', [
                'exception' => $e,
            ]);

            return new JsonResponse([
                'status' => 'error',
                'checks' => $checks,
                'error' => 'unavailable',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $this->pingRedis($this->redisUrl);
            $checks['redis'] = true;
        } catch (Throwable $e) {
            $this->logger->error('Readiness probe failed (redis).', [
                'exception' => $e,
            ]);

            return new JsonResponse([
                'status' => 'error',
                'checks' => $checks,
                'error' => 'unavailable',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse([
            'status' => 'ok',
            'checks' => $checks,
        ]);
    }

    private function pingRedis(string $url): void
    {
        $parts = parse_url($url);
        if (!\is_array($parts) || !isset($parts['host'])) {
            throw new RuntimeException('Invalid REDIS_URL');
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 6379;
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, (int) $port, $errno, $errstr, 2.0);
        if (false === $fp) {
            throw new RuntimeException(\sprintf('Redis unreachable: %s', '' !== $errstr ? $errstr : (string) $errno));
        }

        $password = rawurldecode((string) ($parts['pass'] ?? ''));
        if ('' !== $password) {
            $user = (string) ($parts['user'] ?? '');
            $auth = '' !== $user
                ? \sprintf("AUTH %s %s\r\n", $user, $password)
                : \sprintf("AUTH %s\r\n", $password);
            fwrite($fp, $auth);
            $authResponse = fgets($fp, 64);
            if (!\is_string($authResponse) || !str_starts_with($authResponse, '+OK')) {
                fclose($fp);
                throw new RuntimeException('Redis AUTH failed');
            }
        }

        fwrite($fp, "PING\r\n");
        $response = fgets($fp, 64);
        fclose($fp);
        if (!\is_string($response) || !str_starts_with($response, '+PONG')) {
            throw new RuntimeException('Redis PING failed');
        }
    }
}
