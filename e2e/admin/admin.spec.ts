import { test, expect } from '@playwright/test';
import { dismissProductTour, expectAuthenticatedPage } from '../support/helpers';

test.describe('Administration', () => {
  test('admin hub loads', async ({ page }) => {
    await page.goto('/admin');
    await dismissProductTour(page);
    await expect(page).toHaveURL(/\/admin/);
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('identity admin pages load', async ({ page }) => {
    for (const path of [
      '/admin/users',
      '/admin/users/new',
      '/admin/groups',
      '/admin/groups/new',
      '/admin/social-login',
    ]) {
      await expectAuthenticatedPage(page, path);
    }
  });

  test('settings pages load', async ({ page }) => {
    for (const path of [
      '/settings/mailer',
      '/settings/appearance',
      '/settings/instance-config',
    ]) {
      await expectAuthenticatedPage(page, path);
    }
  });

  test('kit admin panels load', async ({ page }) => {
    const paths = [
      '/admin/http-log',
      '/admin/menus/',
      '/admin/_routing/',
      '/breadcrumb-kit-admin/',
      '/admin/cookie-consent',
    ];
    for (const path of paths) {
      await expectAuthenticatedPage(page, path);
    }
  });

  test('first admin user activity page when list has links', async ({ page }) => {
    await page.goto('/admin/users');
    await dismissProductTour(page);
    const activity = page.locator('a[href*="/admin/users/"][href*="/activity"]').first();
    if ((await activity.count()) === 0) {
      return;
    }
    await activity.click();
    await expect(page).toHaveURL(/\/admin\/users\/.+\/activity/);
    await expect(page).not.toHaveURL(/\/login/);
  });
});
