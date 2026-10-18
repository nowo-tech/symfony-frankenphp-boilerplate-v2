<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Service;

use App\Identity\Entity\User;
use App\Identity\Service\AccountSocialAccounts;
use Nowo\AuthKitBundle\Entity\SocialLoginAccount;
use Nowo\AuthKitBundle\Profile\ProfileRegistry;
use Nowo\AuthKitBundle\Repository\SocialLoginAccountRepository;
use Nowo\AuthKitBundle\Repository\SocialLoginCredentialRepository;
use Nowo\AuthKitBundle\SocialLogin\SocialLoginGate;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class AccountSocialAccountsTest extends TestCase
{
    public function testIsSocialLoginEnabledDelegates(): void
    {
        $credRepo = $this->getMockBuilder(SocialLoginCredentialRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findEnabledOrdered'])
            ->getMock();
        $credRepo->method('findEnabledOrdered')->willReturn([]);

        $gate = new SocialLoginGate($this->disabledProfileRegistry(), $credRepo);
        $service = new AccountSocialAccounts($gate, $this->createStub(SocialLoginAccountRepository::class));

        self::assertFalse($service->isSocialLoginEnabled());
    }

    public function testLinkedForReturnsEmptyWhenUserNotPersisted(): void
    {
        $repo = $this->createMock(SocialLoginAccountRepository::class);
        $repo->expects(self::never())->method('findBy');

        $service = new AccountSocialAccounts($this->unusedGate(), $repo);
        self::assertSame([], $service->linkedFor(new User()));
    }

    public function testLinkedForQueriesRepository(): void
    {
        $user = new User();
        $prop = new ReflectionProperty(User::class, 'id');
        $prop->setValue($user, 9);

        $account = $this->createMock(SocialLoginAccount::class);
        $repo = $this->createMock(SocialLoginAccountRepository::class);
        $repo->expects(self::once())->method('findBy')->with(
            [
                'userClass' => User::class,
                'userId' => '9',
            ],
            ['provider' => 'ASC', 'id' => 'ASC'],
        )->willReturn([$account]);

        $service = new AccountSocialAccounts($this->unusedGate(), $repo);
        self::assertSame([$account], $service->linkedFor($user));
    }

    private function unusedGate(): SocialLoginGate
    {
        return new ReflectionClass(SocialLoginGate::class)->newInstanceWithoutConstructor();
    }

    private function disabledProfileRegistry(): ProfileRegistry
    {
        return new ProfileRegistry([
            'default' => [
                'user_class' => User::class,
                'user_identifier_field' => 'email',
                'registration_role' => 'ROLE_ADMIN',
                'registration_mode' => 'first_user_only',
                'login_fields' => ['identifier', 'password'],
                'remember_me' => ['enabled' => false],
                'password_strength' => ['enabled' => false],
                'registration_fields' => ['email', 'password'],
                'templates' => [],
                'css' => [
                    'button_class' => 'btn',
                    'secondary_button_class' => 'btn-secondary',
                ],
                'embed' => ['mode' => 'disabled'],
                'password_reset' => ['mode' => 'disabled'],
                'magic_login' => ['mode' => 'disabled'],
                'social_login' => ['mode' => 'disabled'],
                'qr_login' => ['mode' => 'disabled'],
                'routes' => [],
                'firewall' => 'main',
                'login_success_route' => 'dashboard_home',
            ],
        ], 'default');
    }
}
