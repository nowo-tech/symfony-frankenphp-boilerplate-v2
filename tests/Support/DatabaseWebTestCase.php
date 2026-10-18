<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Identity\Entity\User;
use App\Identity\Service\InstanceRbacSeeder;
use App\Setup\Demo\BreadcrumbDemoSeeder;
use App\Setup\Demo\CookieConsentDemoSeeder;
use App\Setup\Demo\DashboardMenuDemoSeeder;
use App\Shared\Settings\Repository\InstanceSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use FilesystemIterator;
use Nowo\DashboardMenuBundle\Repository\MenuRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests against a disposable SQLite database (`var/cache/test/phpunit.db`).
 *
 * Each test deletes the DB file and recreates the schema so suites stay isolated
 * (dropSchema alone is unreliable with SQLite + foreign keys).
 */
abstract class DatabaseWebTestCase extends WebTestCase
{
    /**
     * When true (default), menus/breadcrumbs/cookie consent/RBAC permissions are seeded after schema create so
     * {@see PlatformCatalogsSetupRedirectSubscriber} does not force HTML traffic to /setup.
     * Opt out in cold-start tests that need empty catalogs.
     */
    protected bool $autoSeedPlatformCatalogs = true;

    /**
     * When true (default), InstanceSettings is marked setup-completed so the SiteBackup durable-done short-circuit
     * sends `/setup` traffic home. Cold-start tests of the setup wizard / token gate opt out.
     */
    protected bool $markSetupCompleted = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetTestDatabase();
    }

    /**
     * Wipe SQLite file DB, recreate schema, then shut down the kernel so each
     * test boots a clean client via createClient() / bootWithDemoUser().
     */
    private function resetTestDatabase(): void
    {
        static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $conn = $em->getConnection();
        $params = $conn->getParams();
        $path = $params['path'] ?? null;

        $em->close();
        $conn->close();
        self::ensureKernelShutdown();

        $this->removeSqliteFiles(\is_string($path) ? $path : null);
        $this->clearRateLimiterCache();
        $this->clearSiteBackupState();

        static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        if ([] !== $meta) {
            $schemaTool->createSchema($meta);
        }
        if ($this->markSetupCompleted) {
            $this->seedTestInstanceDefaults();
        }
        if ($this->autoSeedPlatformCatalogs) {
            $this->seedPlatformCatalogs();
        }
        self::ensureKernelShutdown();
    }

    /**
     * Drop SiteBackup markers/progress so prior host runs do not skip or bounce `/setup`.
     */
    private function clearSiteBackupState(): void
    {
        $projectDir = \dirname(__DIR__, 2);
        $dir = $projectDir.'/var/site-backup';
        if (!is_dir($dir)) {
            return;
        }

        foreach (['setup.done', 'setup.required', 'setup-progress.json'] as $name) {
            $file = $dir.'/'.$name;
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Delete the SQLite main DB plus WAL/SHM sidecars.
     */
    private function removeSqliteFiles(?string $path): void
    {
        $candidates = [];
        if (null !== $path && '' !== $path) {
            $candidates[] = $path;
        }
        $projectDir = \dirname(__DIR__, 2);
        $candidates[] = $projectDir.'/var/cache/test/phpunit.db';
        $candidates[] = $projectDir.'/var/test.db';
        $candidates[] = '/tmp/symfony-frankenphp-boilerplate-v2-phpunit.db';
        $candidates[] = '/dev/shm/symfony-frankenphp-boilerplate-v2-phpunit.db';
        $envUrl = $_SERVER['APP_TEST_DATABASE_URL'] ?? null;
        if (\is_string($envUrl) && str_starts_with($envUrl, 'sqlite:///')) {
            $candidates[] = substr($envUrl, \strlen('sqlite:///'));
        }

        foreach (array_unique($candidates) as $file) {
            $dir = \dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            foreach ([$file, $file.'-wal', $file.'-shm', $file.'-journal'] as $target) {
                if (is_file($target) && !@unlink($target) && is_file($target)) {
                    throw new RuntimeException('Unable to remove SQLite test database file: '.$target);
                }
            }
        }
    }

    /**
     * login_throttling uses a filesystem cache pool in test; clear it so attempts
     * do not leak across tests (ArrayAdapter would reset, but wipes mid-request).
     */
    private function clearRateLimiterCache(): void
    {
        $projectDir = \dirname(__DIR__, 2);
        $poolDir = $projectDir.'/var/cache/test/pools';
        if (!is_dir($poolDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($poolDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * @return array{0: KernelBrowser, 1: User}
     */
    protected function bootWithDemoUser(string $email = 'user@example.com', string $password = 'secret'): array
    {
        $client = static::createClient();
        $this->seedPlatformCatalogs();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setDisplayName('Test User');
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return [$client, $user];
    }

    /**
     * @deprecated use {@see bootWithDemoUser()} — kept for gradual test migration
     *
     * @return array{0: KernelBrowser, 1: User}
     */
    protected function bootWithDemoProject(string $email = 'user@example.com', string $password = 'secret'): array
    {
        return $this->bootWithDemoUser($email, $password);
    }

    /**
     * Install menus / breadcrumbs / cookie consent / RBAC permissions so admin HTML is not forced to /setup.
     */
    protected function seedPlatformCatalogs(): void
    {
        self::getContainer()->get(DashboardMenuDemoSeeder::class)->seedIfEmpty();
        self::getContainer()->get(BreadcrumbDemoSeeder::class)->seedIfEmpty();
        self::getContainer()->get(CookieConsentDemoSeeder::class)->seedIfEmpty();
        self::getContainer()->get(InstanceRbacSeeder::class)->seedIfEmpty();
        // MenuRepository memoizes misses; clear after seed so PlatformBootstrapState sees new rows.
        self::getContainer()->get(MenuRepository::class)->reset();
    }

    /**
     * Mark setup completed so SiteBackup gate does not redirect HTML traffic.
     */
    protected function seedTestInstanceDefaults(): void
    {
        $repository = self::getContainer()->get(InstanceSettingsRepository::class);
        $settings = $repository->getOrCreate();
        if (null === $settings->getSetupCompletedAt()) {
            $settings->markSetupCompleted();
            $repository->save($settings);
        }
    }

    /**
     * Undo {@see seedTestInstanceDefaults()} for one test (setup wizard reachable again).
     */
    protected function clearSetupCompleted(): void
    {
        $repository = self::getContainer()->get(InstanceSettingsRepository::class);
        $settings = $repository->getOrCreate();
        $settings->clearSetupCompleted();
        $repository->save($settings);
    }

    protected function login(KernelBrowser $client, User $user): void
    {
        $client->loginUser($user);
    }
}
