<?php

declare(strict_types=1);

namespace App\Tests\Functional\Identity;

use App\Setup\Demo\DashboardMenuDemoSeeder;
use App\Tests\Support\DatabaseWebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class AdminCreateModalsTest extends DatabaseWebTestCase
{
    public function testAdminUsersNewRedirectsToCreateModalQuery(): void
    {
        [$client, $admin] = $this->bootWithDemoProject('admin-create-modal-user@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        self::getContainer()->get('doctrine')->getManager()->flush();
        self::getContainer()->get(DashboardMenuDemoSeeder::class)->seedIfEmpty();
        $this->login($client, $admin);

        $client->request(Request::METHOD_GET, '/admin/users/new');
        self::assertResponseRedirects('/admin/users?new=1');

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="admin-user-create"]');
        self::assertCount(1, $crawler->filter('[data-confirm-dialog-open-on-connect-value="true"]'));
        self::assertSelectorExists('form[action$="/admin/users/new"]');
    }

    public function testAdminGroupsNewRedirectsToCreateModalQuery(): void
    {
        [$client, $admin] = $this->bootWithDemoProject('admin-create-modal-group@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        self::getContainer()->get('doctrine')->getManager()->flush();
        self::getContainer()->get(DashboardMenuDemoSeeder::class)->seedIfEmpty();
        $this->login($client, $admin);

        $client->request(Request::METHOD_GET, '/admin/groups/new');
        self::assertResponseRedirects('/admin/groups?new=1');

        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-testid="admin-group-create"]');
        self::assertCount(1, $crawler->filter('[data-confirm-dialog-open-on-connect-value="true"]'));
        self::assertSelectorExists('form[action$="/admin/groups/new"]');
    }
}
