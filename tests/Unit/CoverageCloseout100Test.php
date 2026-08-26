<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Identity\AuthKit\AuthKitMagicLoginMailNotifier;
use App\Identity\AuthKit\AuthKitPasswordResetMailNotifier;
use App\Identity\AuthKit\MailerGatedAuthKitRouteSubscriber;
use App\Identity\AuthKit\PasswordResetRateLimitSubscriber;
use App\Identity\Entity\InstancePermission;
use App\Identity\Entity\User;
use App\Identity\Entity\UserAction;
use App\Identity\Entity\UserGroup;
use App\Identity\Entity\UserGroupMembership;
use App\Identity\EventSubscriber\GuestSessionLocaleSubscriber;
use App\Identity\EventSubscriber\UserPreferredLocaleSubscriber;
use App\Identity\Form\AccountDisplayType;
use App\Identity\Form\AdminInstancePermissionType;
use App\Identity\Repository\InstancePermissionRepository;
use App\Identity\Repository\InstanceRoleRepository;
use App\Identity\Repository\UserActionRepository;
use App\Identity\Repository\UserGroupMembershipRepository;
use App\Identity\Security\InstancePermissionVoter;
use App\Identity\Service\AccountDataExporter;
use App\Identity\Service\AccountSocialAccounts;
use App\Identity\Service\InstanceRbacSeeder;
use App\Identity\UserActionType;
use App\Setup\Command\SeedPlatformCommand;
use App\Setup\Demo\BreadcrumbDemoSeeder;
use App\Setup\Demo\CookieConsentDemoSeeder;
use App\Setup\Demo\DashboardMenuDemoSeeder;
use App\Setup\PlatformCatalogsSetupRedirectSubscriber;
use App\Setup\SiteBackupSecurityDefaultsGuard;
use App\Shared\Appearance\AppearanceSettingsSection;
use App\Shared\Appearance\Form\SiteAppearanceType;
use Nowo\FormKitBundle\Form\CsrfOnlyFormFactory;
use App\Shared\Http\SafeInternalRedirect;
use App\Shared\Mailer\ConfiguredMailer;
use App\Shared\Mailer\MailerDsnAudit;
use App\Shared\Mailer\MailerDsnValidator;
use App\Shared\Menu\AdministrationMenuCurrentMatcher;
use App\Shared\Menu\PreferencesMenuCurrentMatcher;
use Nowo\DashboardMenuBundle\Entity\MenuItem;
use App\Shared\Rbac\RbacPermissionTranslator;
use App\Shared\Settings\Entity\InstanceSettings;
use App\Shared\Settings\Form\InstanceMailerSettingsType;
use App\Shared\Settings\Repository\InstanceSettingsRepository;
use App\Shared\Settings\Service\PlatformBootstrapState;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use InvalidArgumentException;
use Nowo\AuthKitBundle\Enum\PasswordResetDeliveryMode;
use Nowo\AuthKitBundle\MagicLogin\MagicLoginNotificationContext;
use Nowo\AuthKitBundle\PasswordReset\PasswordResetNotificationContext;
use Nowo\AuthKitBundle\PasswordReset\PasswordResetTokenResult;
use Nowo\AuthKitBundle\Repository\SocialLoginAccountRepository;
use Nowo\AuthKitBundle\SocialLogin\SocialLoginGate;
use Nowo\BreadcrumbKitBundle\Repository\BreadcrumbCollectionRepository;
use Nowo\CookieConsentBundle\Repository\CookieConsentConfigRepository;
use Nowo\DashboardMenuBundle\Repository\MenuRepository;
use Nowo\PasswordPolicyBundle\Model\PasswordHistoryInterface;
use Nowo\SiteBackupBundle\Routing\SetupPathPrefixResolver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CoverageCloseout100Test extends KernelTestCase
{
    public function testSeedPlatformCommandSuccessAndFailureBranches(): void
    {
        $breadcrumb = $this->createStub(BreadcrumbDemoSeeder::class);
        $breadcrumb->method('seedIfEmpty')->willReturn(true);
        $menus = $this->createStub(DashboardMenuDemoSeeder::class);
        $menus->method('seedIfEmpty')->willReturn(true);
        $cookies = $this->createStub(CookieConsentDemoSeeder::class);
        $cookies->method('seedIfEmpty')->willReturn(true);
        $rbac = $this->createStub(InstanceRbacSeeder::class);
        $rbac->method('seedIfEmpty')->willReturn(true);

        $tester = new CommandTester(new SeedPlatformCommand($menus, $breadcrumb, $cookies, $rbac));
        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Seeded / updated default breadcrumb collection', $tester->getDisplay());
        self::assertStringContainsString('Seeded / updated navigation menus', $tester->getDisplay());
        self::assertStringContainsString('Seeded / updated cookie consent', $tester->getDisplay());
        self::assertStringContainsString('Seeded / updated instance permission catalog', $tester->getDisplay());

        $failing = $this->createStub(BreadcrumbDemoSeeder::class);
        $failing->method('seedIfEmpty')->willThrowException(new RuntimeException('no schema'));
        $failTester = new CommandTester(new SeedPlatformCommand(
            $this->createStub(DashboardMenuDemoSeeder::class),
            $failing,
            $this->createStub(CookieConsentDemoSeeder::class),
            $this->createStub(InstanceRbacSeeder::class),
        ));
        self::assertSame(1, $failTester->execute([]));
        self::assertStringContainsString('Platform seed failed', $failTester->getDisplay());
        self::assertStringContainsString('no schema', $failTester->getDisplay());
    }

    public function testPlatformCatalogsSetupRedirectBranches(): void
    {
        $resolver = new SetupPathPrefixResolver(new RequestStack(), '/setup', 'never', 'en', ['en']);

        $stateJson = $this->createMock(PlatformBootstrapState::class);
        $stateJson->expects(self::never())->method('needsPlatformSeed');
        $subJson = new PlatformCatalogsSetupRedirectSubscriber(
            $stateJson,
            $this->createStub(AuthorizationCheckerInterface::class),
            $resolver,
            '/setup',
            true,
        );
        $json = Request::create('/dashboard', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $subJson->onKernelRequest($this->requestEvent($json));

        $stateThrow = $this->createStub(PlatformBootstrapState::class);
        $stateThrow->method('needsPlatformSeed')->willThrowException(new RuntimeException('db down'));
        $subThrow = new PlatformCatalogsSetupRedirectSubscriber(
            $stateThrow,
            $this->createStub(AuthorizationCheckerInterface::class),
            $resolver,
            '/setup',
            true,
        );
        $subThrow->onKernelRequest($this->requestEvent(Request::create('/dashboard')));

        $stateNeed = $this->createStub(PlatformBootstrapState::class);
        $stateNeed->method('needsPlatformSeed')->willReturn(true);
        $auth = $this->createStub(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->willReturn(false);
        $sub = new PlatformCatalogsSetupRedirectSubscriber($stateNeed, $auth, $resolver, '/setup', true);

        $excluded = Request::create('/dashboard');
        $excluded->attributes->set('_route', 'nowo_auth_kit_login');
        $eventExcluded = $this->requestEvent($excluded);
        $sub->onKernelRequest($eventExcluded);
        self::assertNull($eventExcluded->getResponse());

        $gate = Request::create('/dashboard');
        $gate->attributes->set('_route', 'app_dashboard');
        $eventGate = $this->requestEvent($gate);
        $sub->onKernelRequest($eventGate);
        self::assertSame('/setup', $eventGate->getResponse()?->getTargetUrl());
    }

    public function testConfiguredMailerEnvFallbackCacheAndSampleGuard(): void
    {
        $settings = InstanceSettings::defaults();
        $repo = $this->createStub(InstanceSettingsRepository::class);
        $repo->method('getOrCreate')->willReturn($settings);
        $mailer = new ConfiguredMailer($repo, new MailerDsnValidator(), '  smtp://localhost  ', 'test');
        self::assertSame('smtp://localhost', $mailer->getEffectiveDsn());

        $emptyEnv = new ConfiguredMailer($repo, new MailerDsnValidator(), '   ', 'test');
        self::assertSame('null://null', $emptyEnv->getEffectiveDsn());

        $this->expectException(RuntimeException::class);
        $mailer->sendSample('a@example.com', $this->createStub(TranslatorInterface::class));
    }

    public function testConfiguredMailerReusesResolvedTransport(): void
    {
        $settings = InstanceSettings::defaults();
        $settings->setMailerDsn('null://null');
        $repo = $this->createStub(InstanceSettingsRepository::class);
        $repo->method('getOrCreate')->willReturn($settings);
        $mailer = new ConfiguredMailer($repo, new MailerDsnValidator(), 'null://null', 'test');
        $message = new Email()->from('a@example.com')->to('b@example.com')->subject('x')->text('y');
        $mailer->send($message);
        $mailer->send($message);
        $this->addToAssertionCount(1);
    }

    public function testUserGroupHasUserAndMembershipCreatedAt(): void
    {
        $group = new UserGroup();
        $user = new User();
        $user->setEmail('g@example.com');
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, 9);
        self::assertFalse($group->hasUser($user));

        $membership = new UserGroupMembership();
        $membership->setUser($user);
        $group->addMembership($membership);
        self::assertTrue($group->hasUser($user));
        self::assertInstanceOf(DateTimeImmutable::class, $membership->getCreatedAt());
    }

    public function testUserActionGetIdAndPasswordHistoryTypeGuard(): void
    {
        $action = new UserAction();
        $action->setAction(UserActionType::UserCreated);
        self::assertNull($action->getId());

        $user = new User();
        $user->setEmail('p@example.com');
        $user->setDisplayName('P');
        self::assertCount(0, $user->getInstanceRoles());

        $this->expectException(InvalidArgumentException::class);
        $user->addPasswordHistory($this->createStub(PasswordHistoryInterface::class));
    }

    public function testMenuMatchersEdgeBranches(): void
    {
        $prefs = new PreferencesMenuCurrentMatcher();
        $profile = new MenuItem();
        $profile->setRouteName('account_profile');
        $request = Request::create('/account/profile');
        $request->attributes->set('_route', 'account_profile');
        self::assertTrue($prefs->isCurrent($profile, $request, '/account/profile'));

        $admin = new AdministrationMenuCurrentMatcher();
        $item = new MenuItem();
        $item->setRouteName('nowo_routing_kit_panel');
        $routingRequest = Request::create('/admin/_routing/edit');
        $routingRequest->attributes->set('_route', 'nowo_routing_kit_panel_edit');
        self::assertTrue($admin->isCurrent($item, $routingRequest, '/admin/_routing'));
    }

    public function testLocaleSubscribersBranches(): void
    {
        $user = new User();
        $user->setEmail('loc@example.com');
        $user->setDisplayName('L');
        $user->setPreferredLocale(null);

        $storage = new TokenStorage();
        $storage->setToken(new UsernamePasswordToken($user, 'main', ['ROLE_USER']));
        $translator = $this->createStub(TranslatorInterface::class);
        $sub = new UserPreferredLocaleSubscriber($storage, $translator, 'en');

        $authKit = Request::create('/login');
        $authKit->attributes->set('_route', 'nowo_auth_kit_login');
        $sub->onKernelRequest($this->requestEvent($authKit));

        $withQs = Request::create('/dashboard', 'GET', ['foo' => '1', '_locale' => 'es']);
        $eventQs = $this->requestEvent($withQs);
        $sub->onKernelRequest($eventQs);
        self::assertSame('/dashboard?foo=1', $eventQs->getResponse()?->getTargetUrl());

        $blankDefault = new UserPreferredLocaleSubscriber($storage, $translator, '   ');
        $blankDefault->onKernelRequest($this->requestEvent(Request::create('/dashboard')));

        $guestStorage = new TokenStorage();
        $guestStorage->setToken(new NullToken());
        /** @var TranslatorInterface&LocaleAwareInterface&\PHPUnit\Framework\MockObject\MockObject $guestTranslator */
        $guestTranslator = $this->createMock(LocaleAwareTranslator::class);
        $guest = new GuestSessionLocaleSubscriber($guestStorage, $guestTranslator, ['en', 'es'], 'en');

        $guest->onKernelRequest($this->requestEvent(Request::create('/public')));

        $sessionReq = Request::create('/public');
        $sessionReq->setSession(new Session(new MockArraySessionStorage()));
        $sessionReq->getSession()->set('_locale', 'zz');
        $guestTranslator->expects(self::once())->method('setLocale')->with('en');
        $guest->onKernelRequest($this->requestEvent($sessionReq));
        self::assertSame('en', $sessionReq->getLocale());
    }

    public function testInstancePermissionVoterDeniesNonUserAndNullId(): void
    {
        $voter = new InstancePermissionVoter(
            $this->createStub(InstancePermissionRepository::class),
            $this->createStub(InstanceRoleRepository::class),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(new NullToken(), null, ['admin.users.manage']),
        );

        $user = new User();
        $permissions = $this->createStub(InstancePermissionRepository::class);
        $permissions->method('findOneByKey')->willReturn((new InstancePermission())->setKey('admin.users.manage'));
        $voter2 = new InstancePermissionVoter($permissions, $this->createStub(InstanceRoleRepository::class));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter2->vote(new UsernamePasswordToken($user, 'main', ['ROLE_USER']), null, ['admin.users.manage']),
        );
    }

    public function testAuthKitNotifiersEarlyReturns(): void
    {
        $offSettings = InstanceSettings::defaults();
        $offRepo = $this->createStub(InstanceSettingsRepository::class);
        $offRepo->method('getOrCreate')->willReturn($offSettings);
        $mailerOff = new ConfiguredMailer($offRepo, new MailerDsnValidator(), 'null://null', 'test');

        $onSettings = InstanceSettings::defaults();
        $onSettings->setMailerDsn('smtp://user:pass@127.0.0.1:2525');
        $onRepo = $this->createStub(InstanceSettingsRepository::class);
        $onRepo->method('getOrCreate')->willReturn($onSettings);
        $mailerOn = new ConfiguredMailer($onRepo, new MailerDsnValidator(), 'null://null', 'test');

        $translator = $this->createStub(TranslatorInterface::class);
        $token = new PasswordResetTokenResult(new User(), 'tok', new DateTimeImmutable('+1 hour'), PasswordResetDeliveryMode::Link);

        (new AuthKitPasswordResetMailNotifier($mailerOff, $translator))->notify(
            $token,
            new PasswordResetNotificationContext('a@example.com', 'https://x', PasswordResetDeliveryMode::Link),
        );
        (new AuthKitPasswordResetMailNotifier($mailerOn, $translator))->notify(
            $token,
            new PasswordResetNotificationContext('not-an-email', 'https://x', PasswordResetDeliveryMode::Link),
        );
        (new AuthKitMagicLoginMailNotifier($mailerOff, $translator))->notify(
            new MagicLoginNotificationContext('a@example.com', 'https://x', new DateTimeImmutable('+1 hour')),
        );
        (new AuthKitMagicLoginMailNotifier($mailerOn, $translator))->notify(
            new MagicLoginNotificationContext('bad', 'https://x', new DateTimeImmutable('+1 hour')),
        );
        $this->addToAssertionCount(1);
    }

    public function testAccountDataExporterSkipsNonPasswordHistory(): void
    {
        $user = new User();
        $user->setEmail('exp@example.com');
        (new \ReflectionProperty(User::class, 'passwordHistory'))->setValue(
            $user,
            new ArrayCollection([$this->createStub(PasswordHistoryInterface::class)]),
        );

        $actions = $this->createStub(UserActionRepository::class);
        $actions->method('findForUser')->willReturn([]);
        $memberships = $this->createStub(UserGroupMembershipRepository::class);
        $memberships->method('findByUser')->willReturn([]);

        $doc = (new AccountDataExporter(
            $memberships,
            $actions,
            new AccountSocialAccounts(
                (new ReflectionClass(SocialLoginGate::class))->newInstanceWithoutConstructor(),
                $this->createStub(SocialLoginAccountRepository::class),
            ),
        ))->export($user);

        self::assertSame(0, $doc['password_history']['count']);
    }

    public function testInstancePermissionSyncSkipsNonStringLocale(): void
    {
        $permission = new InstancePermission();
        $permission->syncTranslations([0 => 'Name', 'en' => 'English'], [0 => 'Desc']);
        self::assertSame('English', $permission->getNameForLocale('en'));
        self::assertNull($permission->getNameForLocale('0'));
    }

    public function testRbacDescriptionNullFallback(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn('en');
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => $id);
        $rbac = new RbacPermissionTranslator($translator);
        $permission = (new InstancePermission())->setKey('admin.custom.x')->setName('X')->setDescription(null);
        self::assertNull($rbac->description($permission));
    }

    public function testSiteBackupRejectsEmptyPanelHash(): void
    {
        $guard = new SiteBackupSecurityDefaultsGuard('prod', 'unique-token', '');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SITE_BACKUP_PASSWORD_HASH must be set');
        $guard->assertProductionSecretsSafe();
    }

    public function testCsrfOnlyFormFactoryUnnamedCreate(): void
    {
        self::bootKernel();
        $factory = new CsrfOnlyFormFactory(self::getContainer()->get(FormFactoryInterface::class));
        $form = $factory->create('/action', 'token');
        self::assertSame('', $form->getName());
    }

    public function testMailerGatedUsesDefaultLocale(): void
    {
        $settings = InstanceSettings::defaults();
        $repo = $this->createStub(InstanceSettingsRepository::class);
        $repo->method('getOrCreate')->willReturn($settings);
        $mailer = new ConfiguredMailer($repo, new MailerDsnValidator(), 'null://null', 'test');
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects(self::once())->method('generate')->with('nowo_auth_kit_login', ['_locale' => 'en'])->willReturn('/login');
        $sub = new MailerGatedAuthKitRouteSubscriber($mailer, $urls, 'en');
        $request = Request::create('/reset');
        $request->attributes->set('_route', 'nowo_auth_kit_reset_password_request');
        $event = $this->requestEvent($request);
        $sub->onKernelRequest($event);
        self::assertSame('/login', $event->getResponse()?->getTargetUrl());
    }

    public function testAccountDisplayTypeRejectsUnknownSection(): void
    {
        self::bootKernel();
        $type = self::getContainer()->get(AccountDisplayType::class);
        $this->expectException(InvalidArgumentException::class);
        $type->buildForm(
            $this->createStub(FormBuilderInterface::class),
            ['section' => 'security', 'enabled_locales' => ['en']],
        );
    }

    public function testSafeInternalRedirectRejectsDecodedProtocolRelative(): void
    {
        $request = Request::create('https://shell.example/dashboard');
        self::assertSame('/fallback', SafeInternalRedirect::resolve($request, '/%2f%2fevil.example', '/fallback'));
    }

    public function testPlatformBootstrapMissingBreadcrumbCollection(): void
    {
        $breadcrumbs = $this->createStub(BreadcrumbCollectionRepository::class);
        $breadcrumbs->method('findOneByCodeAndContextKey')->willReturn(null);
        $state = new PlatformBootstrapState(
            $this->createStub(MenuRepository::class),
            $breadcrumbs,
            $this->createStub(CookieConsentConfigRepository::class),
        );
        self::assertFalse($state->hasDefaultBreadcrumbs());
    }

    public function testMailerDsnAuditParseUrlFalse(): void
    {
        self::assertSame(['scheme' => null, 'host' => null], MailerDsnAudit::redact('http://'));
        self::assertSame(['scheme' => null, 'host' => null], MailerDsnAudit::redact('http:///'));
    }

    public function testUserActionRepositoryPlatformPredicates(): void
    {
        $repo = (new ReflectionClass(UserActionRepository::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(UserActionRepository::class, 'contextUuidPredicate');
        self::assertStringContainsString('JSON_UNQUOTE', $method->invoke($repo, new MySQLPlatform(), 'groupUuid'));
        $this->expectException(RuntimeException::class);
        $method->invoke($repo, new PostgreSQLPlatform(), 'groupUuid');
    }

    public function testInstanceMailerSettingsRejectsNonStringDsn(): void
    {
        self::bootKernel();
        $type = self::getContainer()->get(InstanceMailerSettingsType::class);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->method('setTranslationDomain')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')
            ->with('instance_mailer.plain_mailer_dsn.invalid')
            ->willReturn($builder);
        $type->validatePlainDsn(['not', 'string'], $context);
    }

    public function testSiteAppearanceThemesAndDefaultColorSubtab(): void
    {
        self::bootKernel();
        $forms = self::getContainer()->get(FormFactoryInterface::class);
        $themes = $forms->create(SiteAppearanceType::class, null, [
            'section' => AppearanceSettingsSection::Themes,
            'csrf_protection' => false,
        ]);
        self::assertCount(0, $themes->all());

        $colors = $forms->create(SiteAppearanceType::class, null, [
            'section' => AppearanceSettingsSection::Colors,
            'subtab' => null,
            'csrf_protection' => false,
        ]);
        self::assertTrue($colors->has('accentColor'));
    }

    public function testAdminInstancePermissionEmptyKeyAndSubmitNonEntity(): void
    {
        self::bootKernel();
        $forms = self::getContainer()->get(FormFactoryInterface::class);
        $permission = new InstancePermission();
        $permission->setKey('');
        $permission->setName('');
        $permission->setDescription(null);
        $form = $forms->create(AdminInstancePermissionType::class, $permission, [
            'csrf_protection' => false,
            'enabled_locales' => ['en'],
            'default_locale' => 'en',
        ]);
        self::assertSame('', $form->get('description_en')->getData());

        $nullForm = $forms->create(AdminInstancePermissionType::class, null, [
            'csrf_protection' => false,
            'enabled_locales' => ['en'],
            'default_locale' => 'en',
            'data_class' => null,
        ]);
        $nullForm->submit([
            'key' => 'admin.custom.x',
            'category' => 'access',
            'name_en' => 'X',
            'description_en' => 'Y',
        ]);
        self::assertTrue($nullForm->isSynchronized());
    }

    public function testPasswordResetRateLimitGetAndUnlocalized(): void
    {
        $factory = new RateLimiterFactory([
            'id' => 'pr',
            'policy' => 'fixed_window',
            'limit' => 10,
            'interval' => '1 hour',
        ], new InMemoryStorage());
        $sub = new PasswordResetRateLimitSubscriber($factory, 'prod');

        $get = Request::create('/reset', 'GET');
        $get->attributes->set('_route', 'nowo_auth_kit_reset_password_request');
        $sub->onKernelRequest($this->requestEvent($get));

        $post = Request::create('/reset', 'POST', server: ['REMOTE_ADDR' => '203.0.113.20']);
        $post->attributes->set('_route', 'nowo_auth_kit_reset_password_request_unlocalized');
        $sub->onKernelRequest($this->requestEvent($post));
        $this->addToAssertionCount(1);
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}

interface LocaleAwareTranslator extends TranslatorInterface, LocaleAwareInterface
{
}
