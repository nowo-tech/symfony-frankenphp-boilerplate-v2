<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\AuthKit;

use App\Identity\AuthKit\MagicLoginRateLimitSubscriber;
use App\Identity\AuthKit\PasswordResetRateLimitSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AuthKitRateLimitSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 5]],
            MagicLoginRateLimitSubscriber::getSubscribedEvents(),
        );
        self::assertSame(
            [KernelEvents::REQUEST => ['onKernelRequest', 5]],
            PasswordResetRateLimitSubscriber::getSubscribedEvents(),
        );
    }

    public function testSkipsInTestEnvironment(): void
    {
        $factory = $this->factory(limit: 1);
        $subscriber = new MagicLoginRateLimitSubscriber($factory, 'test');
        $event = $this->postEvent('nowo_auth_kit_magic_login_request');

        $subscriber->onKernelRequest($event);
        $subscriber->onKernelRequest($event);
        // No limiter hit and no exception: the subscriber let both requests through.
        $this->addToAssertionCount(1);
    }

    public function testMagicLoginAllowsThenBlocks(): void
    {
        $factory = $this->factory(limit: 1);
        $subscriber = new MagicLoginRateLimitSubscriber($factory, 'prod');

        $subscriber->onKernelRequest($this->postEvent('nowo_auth_kit_magic_login_request'));

        $this->expectException(TooManyRequestsHttpException::class);
        $subscriber->onKernelRequest($this->postEvent('nowo_auth_kit_magic_login_request_unlocalized'));
    }

    public function testPasswordResetAllowsThenBlocks(): void
    {
        $factory = $this->factory(limit: 1);
        $subscriber = new PasswordResetRateLimitSubscriber($factory, 'prod');

        $subscriber->onKernelRequest($this->postEvent('nowo_auth_kit_reset_password_request'));

        $this->expectException(TooManyRequestsHttpException::class);
        $subscriber->onKernelRequest($this->postEvent('nowo_auth_kit_reset_password_request'));
    }

    public function testIgnoresOtherRoutesAndMethods(): void
    {
        $factory = $this->factory(limit: 1);
        $magic = new MagicLoginRateLimitSubscriber($factory, 'prod');
        $reset = new PasswordResetRateLimitSubscriber($factory, 'prod');

        $magic->onKernelRequest($this->postEvent('other_route'));
        $magic->onKernelRequest($this->event(Request::create('/', Request::METHOD_GET), 'nowo_auth_kit_magic_login_request'));
        $reset->onKernelRequest($this->postEvent('other_route'));

        $subRequest = $this->postEvent('nowo_auth_kit_magic_login_request', HttpKernelInterface::SUB_REQUEST);
        $magic->onKernelRequest($subRequest);
        // No limiter hit and no exception: the subscriber let both requests through.
        $this->addToAssertionCount(1);
    }

    private function factory(int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory([
            'id' => 'test_limiter',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 hour',
        ], new InMemoryStorage());
    }

    private function postEvent(string $route, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = Request::create('/', Request::METHOD_POST, server: ['REMOTE_ADDR' => '203.0.113.10']);
        $request->attributes->set('_route', $route);

        return $this->event($request, $route, $requestType);
    }

    private function event(
        Request $request,
        string $route,
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): RequestEvent {
        $request->attributes->set('_route', $route);
        $kernel = $this->createStub(KernelInterface::class);

        return new RequestEvent($kernel, $request, $requestType);
    }
}
