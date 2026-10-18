import { test, expect } from '@playwright/test';
import { dismissCookieConsent } from '../support/helpers';

/**
 * Security denials — guest must not reach authenticated shell or Administration.
 */
test.describe('Security — access denials', () => {
  test('guest is redirected to login on protected routes', async ({ browser }) => {
    const context = await browser.newContext({
      ignoreHTTPSErrors: true,
      storageState: { cookies: [], origins: [] },
    });
    const page = await context.newPage();
    try {
      for (const path of ['/dashboard', '/admin', '/admin/users', '/account', '/settings/mailer']) {
        await page.goto(path);
        await dismissCookieConsent(page);
        await expect(page, path).toHaveURL(/\/login/);
      }
    } finally {
      await context.close();
    }
  });
});
