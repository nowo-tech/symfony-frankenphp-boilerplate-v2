import { test, expect } from '@playwright/test';
import { dismissCookieConsent, expectGuestPage } from '../support/helpers';

/**
 * Auth security gates — login failure and public auth surfaces.
 */
test.describe('Security — auth gates', () => {
  test('invalid credentials never leave the login page', async ({ browser }) => {
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      storageState: { cookies: [], origins: [] },
    });
    const page = await context.newPage();
    try {
      await page.goto('/login');
      await dismissCookieConsent(page);
      await page.locator('input[name="login_form[_username]"]').fill('nobody@example.invalid');
      await page.locator('input[name="login_form[_password]"]').fill('definitely-wrong-password');
      await page
        .locator('[data-nowo-ui-page="auth"] button[type="submit"], form[name="login_form"] button[type="submit"]')
        .first()
        .click();
      await expect(page).toHaveURL(/\/login/);
      await expect(page).not.toHaveURL(/\/dashboard/);
    } finally {
      await context.close();
    }
  });

  test('password reset stays public and does not expose dashboard', async ({ browser }) => {
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      storageState: { cookies: [], origins: [] },
    });
    const page = await context.newPage();
    try {
      await expectGuestPage(page, '/reset-password');
      await expect(page).not.toHaveURL(/\/dashboard/);
    } finally {
      await context.close();
    }
  });
});
