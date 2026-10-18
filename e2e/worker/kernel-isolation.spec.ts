import { expect, test } from '@playwright/test';
import { expectAuthenticatedPage } from '../support/helpers';
import { expectFrankenPhpRuntime, frankenPhpExpectationFromEnv } from '../support/frankenphp';

const isolated = process.env.PLAYWRIGHT_ISOLATED === '1';
const baseUrl =
  process.env.PLAYWRIGHT_BASE_URL ?? (isolated ? 'https://localhost:9461' : 'https://localhost:9444');

test.describe('FrankenPHP worker kernel isolation', () => {
  test.describe.configure({ mode: 'serial' });

  test('runtime probe matches worker-safe contract', async ({ request }) => {
    await expectFrankenPhpRuntime(request, frankenPhpExpectationFromEnv());
  });

  test('authenticated session does not leak into a fresh guest context', async ({ page, browser }) => {
    const expected = frankenPhpExpectationFromEnv();
    test.skip(expected.mode !== 'worker', 'Session isolation targets shared Kernel worker mode.');

    await expectAuthenticatedPage(page, '/dashboard');

    const guest = await browser.newContext({ ignoreHTTPSErrors: true, storageState: { cookies: [], origins: [] } });
    try {
      const guestPage = await guest.newPage();
      await guestPage.goto(`${baseUrl}/dashboard`, { waitUntil: 'domcontentloaded' });
      await expect(guestPage).toHaveURL(/\/login(\?|$|\/)/);
      await expect(guestPage.locator('input[name="login_form[_username]"]')).toBeVisible();
    } finally {
      await guest.close();
    }
  });
});
