import { expect, type Page } from '@playwright/test';
import { dismissProductTour, waitForPageLoader } from './helpers';

/**
 * SMTP DSN for Settings → Mailer in E2E.
 *
 * Isolated stack starts Compose profile `mail` (service hostname `mailer`).
 */
export function playwrightMailerSmtpDsn(): string {
  if (process.env.PLAYWRIGHT_MAILER_DSN) {
    return process.env.PLAYWRIGHT_MAILER_DSN;
  }

  return 'smtp://mailer:1025';
}

/** Persist a deliverable encrypted Mailer DSN for AuthKit password-reset / magic-login. */
export async function ensureDeliverableMailer(page: Page): Promise<void> {
  await page.goto('/settings/mailer');
  await waitForPageLoader(page);
  await dismissProductTour(page);
  const form = page.locator('form').filter({ has: page.locator('input[name*="[plainMailerDsn]"]') });
  await expect(form).toBeVisible({ timeout: 15_000 });
  await form.locator('input[name*="[plainMailerDsn]"]').fill(playwrightMailerSmtpDsn());
  const from = form.locator('input[name*="[mailerFrom]"]');
  if ((await from.count()) > 0 && (await from.first().inputValue()) === '') {
    await from.first().fill('nowo-shell@example.local');
  }
  await form.locator('button[type="submit"]').first().click();
  await waitForPageLoader(page);
  await expect(page).not.toHaveURL(/\/login/);
}
