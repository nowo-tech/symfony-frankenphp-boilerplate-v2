<?php

declare(strict_types=1);

namespace App\Identity\Command;

use App\Identity\Service\DemoIdentitySeeder;
use App\Setup\Demo\BreadcrumbDemoSeeder;
use App\Setup\Demo\CookieConsentDemoSeeder;
use App\Setup\Demo\DashboardMenuDemoSeeder;
use App\Shared\Settings\Repository\InstanceSettingsRepository;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds a demo admin user. Navigation catalogs: `app:seed-platform`.
 */
#[AsCommand(
    name: 'app:seed-demo',
    description: 'Seed demo admin user (+ optional platform catalogs)',
)]
final class SeedDemoCommand extends Command
{
    public function __construct(
        private readonly DemoIdentitySeeder $demoIdentitySeeder,
        private readonly BreadcrumbDemoSeeder $breadcrumbDemoSeeder,
        private readonly DashboardMenuDemoSeeder $dashboardMenuDemoSeeder,
        private readonly CookieConsentDemoSeeder $cookieConsentDemoSeeder,
        private readonly InstanceSettingsRepository $instanceSettingsRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Demo user email', 'admin@nowo-shell.local')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Demo user password', 'admin123')
            ->addOption('with-platform', null, InputOption::VALUE_NONE, 'Also run platform menu/breadcrumb/cookie-consent seed')
            ->addOption('skip-demo-user', null, InputOption::VALUE_NONE, 'Do not create the demo admin user; use existing ROLE_ADMIN accounts');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');
        $skipDemoUser = (bool) $input->getOption('skip-demo-user');

        if ((bool) $input->getOption('with-platform')) {
            if ($this->breadcrumbDemoSeeder->seedIfEmpty()) {
                $io->success('Seeded / updated default breadcrumb collection');
            }
            if ($this->dashboardMenuDemoSeeder->seedIfEmpty()) {
                $io->success('Seeded / updated navigation menus');
            }
            if ($this->cookieConsentDemoSeeder->seedIfEmpty()) {
                $io->success('Seeded / updated cookie consent profile and inventory');
            }
        }

        try {
            $result = $this->demoIdentitySeeder->seed($email, $password, !$skipDemoUser);
        } catch (LogicException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $user = $result['user'];
        if ($result['user_created']) {
            $io->success(\sprintf('Created demo admin %s', $user->getEmail()));
        } else {
            $io->note(\sprintf('Demo admin already present: %s', $user->getEmail()));
        }

        $settings = $this->instanceSettingsRepository->get();
        if (null === $settings->getSetupCompletedAt()) {
            $settings->markSetupCompleted();
            $this->instanceSettingsRepository->save($settings);
            $io->success('Marked instance setup as completed');
        }

        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $user->getEmail()],
                ['Roles', implode(', ', $user->getRoles())],
            ],
        );

        return Command::SUCCESS;
    }
}
