import { expect, test } from '@playwright/test';
import { DEMO_EMAIL, dismissCookieConsent } from '../support/helpers';
import { ensureDeliverableMailer } from '../support/mailer';
import {
  clearMailpitMessages,
  expectMailpitHealthy,
  getMailpitMessageHtml,
  mailpitBaseUrl,
  waitForMailpitMessage,
} from '../support/mailpit';

/**
 * MAILPIT-002 — password-reset mail delivery into E2E Mailpit.
 *
 * Prereq: `make test-e2e-mailpit` (starts Mailpit on isolated stack, seeds mailer DSN).
 */
test.describe('Mailpit auth delivery', () => {
  test('password-reset mail is delivered to Mailpit', async ({ page, request }) => {
    await expectMailpitHealthy();
    await clearMailpitMessages();
    await ensureDeliverableMailer(page);

    await page.context().clearCookies();
    await page.goto('/reset-password');
    await dismissCookieConsent(page);
    const emailInput = page
      .locator('input[type="email"], input[name*="identifier"], input[name*="email"]')
      .first();
    await expect(emailInput).toBeVisible({ timeout: 15_000 });
    await emailInput.fill(DEMO_EMAIL);
    await Promise.all([
      page.waitForResponse((r) => r.request().method() === 'POST', { timeout: 30_000 }),
      page.locator('form button[type="submit"], button[type="submit"]').first().click(),
    ]);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).not.toContainText('Whoops, looks like something went wrong');

    const message = await waitForMailpitMessage({
      toAddress: DEMO_EMAIL,
      subjectRe: /reset|password|contrase|restablec/i,
      timeoutMs: 45_000,
    });
    expect(message.ID).toBeTruthy();

    const body = await getMailpitMessageHtml(message.ID);
    expect(body.length).toBeGreaterThan(20);
    expect(body).toMatch(/reset-password|token|code|otp/i);

    const info = await request.get(`${mailpitBaseUrl()}/api/v1/info`);
    expect(info.ok()).toBeTruthy();
  });
});
