<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Service;

use App\Identity\Entity\PasswordHistory;
use App\Identity\Entity\User;
use App\Identity\Entity\UserAction;
use App\Identity\Entity\UserGroup;
use App\Identity\Entity\UserGroupMembership;
use App\Identity\Repository\UserActionRepository;
use App\Identity\Repository\UserGroupMembershipRepository;
use App\Identity\Service\AccountDataExporter;
use App\Identity\Service\AccountSocialAccounts;
use App\Identity\UserActionType;
use DateTimeImmutable;
use Nowo\AuthKitBundle\Entity\SocialLoginAccount;
use Nowo\AuthKitBundle\Repository\SocialLoginAccountRepository;
use Nowo\AuthKitBundle\SocialLogin\SocialLoginGate;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class AccountDataExporterTest extends TestCase
{
    public function testExportBuildsScrubbedDocument(): void
    {
        $user = new User();
        $user->setEmail('person@example.com');
        $user->setDisplayName('Person');
        $user->setPhone('+15551212');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $user->setPreferredTheme('dark');

        $history = new PasswordHistory();
        $history->setUser($user);
        $history->setPassword('hash');
        $history->setCreatedAt(new DateTimeImmutable('2024-01-02T03:04:05+00:00'));
        $user->addPasswordHistory($history);

        $action = new UserAction();
        $action->setAction(UserActionType::MagicLoginRequested);
        $action->setContext(['email' => 'person@example.com']);

        $group = new UserGroup();
        $group->setName('Editors');
        $membership = new UserGroupMembership();
        $membership->setUserGroup($group);
        $membership->setUser($user);

        $orphanMembership = new UserGroupMembership();
        $orphanMembership->setUser($user);

        $social = $this->createMock(SocialLoginAccount::class);
        $social->method('getProvider')->willReturn('github');
        $social->method('getEmail')->willReturn('gh@example.com');
        $social->method('getCreatedAt')->willReturn(new DateTimeImmutable('2024-02-01T00:00:00+00:00'));

        $actions = $this->createMock(UserActionRepository::class);
        $actions->method('findForUser')->willReturn([$action]);

        $memberships = $this->createMock(UserGroupMembershipRepository::class);
        $memberships->method('findByUser')->willReturn([$membership, $orphanMembership]);

        $socialRepo = $this->createMock(SocialLoginAccountRepository::class);
        $socialRepo->method('findBy')->willReturn([$social]);
        $socialAccounts = new AccountSocialAccounts($this->unusedGate(), $socialRepo);

        // Persisted id required for linkedFor()
        $id = new ReflectionProperty(User::class, 'id');
        $id->setValue($user, 5);

        $doc = new AccountDataExporter($memberships, $actions, $socialAccounts)->export($user);

        self::assertSame('app-account-export/v1', $doc['schema']);
        self::assertSame('person@example.com', $doc['account']['email']);
        self::assertSame(['ROLE_ADMIN'], $doc['account']['roles']);
        self::assertSame('dark', $doc['account']['preferred_theme']);
        self::assertCount(1, $doc['group_memberships']);
        self::assertSame('Editors', $doc['group_memberships'][0]['group_name']);
        self::assertSame(UserActionType::MagicLoginRequested->value, $doc['security_activity'][0]['action']);
        self::assertSame(1, $doc['password_history']['count']);
        self::assertSame('github', $doc['social_accounts'][0]['provider']);
        self::assertSame('gh@example.com', $doc['social_accounts'][0]['provider_email']);
    }

    public function testExportHandlesEmptyCollections(): void
    {
        $user = new User();
        $user->setEmail('empty@example.com');

        $actions = $this->createMock(UserActionRepository::class);
        $actions->method('findForUser')->willReturn([]);
        $memberships = $this->createMock(UserGroupMembershipRepository::class);
        $memberships->method('findByUser')->willReturn([]);
        $socialRepo = $this->createMock(SocialLoginAccountRepository::class);
        $socialRepo->method('findBy')->willReturn([]);
        $socialAccounts = new AccountSocialAccounts($this->unusedGate(), $socialRepo);

        $doc = new AccountDataExporter($memberships, $actions, $socialAccounts)->export($user);

        self::assertSame([], $doc['group_memberships']);
        self::assertSame([], $doc['security_activity']);
        self::assertSame(0, $doc['password_history']['count']);
        self::assertSame([], $doc['social_accounts']);
        self::assertNull($doc['account']['phone_verified_at']);
        self::assertNull($doc['account']['anonymized_at']);
    }

    private function unusedGate(): SocialLoginGate
    {
        return new ReflectionClass(SocialLoginGate::class)->newInstanceWithoutConstructor();
    }
}
