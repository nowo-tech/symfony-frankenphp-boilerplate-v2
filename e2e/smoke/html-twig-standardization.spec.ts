import { expect, test } from '@playwright/test';
import { dismissCookieConsent } from '../support/helpers';
import { assertHtmlTwigStandardization, readResponseHtml } from '../support/html-markup';

/**
 * QA-012 — host Twig HTML is a real document shell with CSP-safe inline style/script.
 */
test.describe('HTML / Twig markup standardization — public', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('public shells ship CSP-safe Twig markup', async ({ page }) => {
    for (const path of ['/login', '/en/login', '/reset-password', '/login/magic', '/en/legal/privacy']) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect([200, 302, 301]).toContain(response!.status());
      const html = await readResponseHtml(response);
      await dismissCookieConsent(page);
      await assertHtmlTwigStandardization(page, html);
    }
  });
});
