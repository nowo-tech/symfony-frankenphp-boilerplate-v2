<?php

declare(strict_types=1);

namespace App\Shared\Health;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
    ) {
    }

    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => false,
            'messenger_async_pending' => null,
        ];

        try {
            $connection = $this->entityManager->getConnection();
            $connection->executeQuery('SELECT 1');
            $checks['database'] = true;
            $queue = $this->messengerQueueHealth->asyncPending();
            $checks['messenger_async_pending'] = $queue['pending'];
        } catch (Throwable $e) {
            $this->logger->error('Readiness probe failed.', [
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
}
