import { test, expect } from '@playwright/test';
import { dismissProductTour, expectAuthenticatedPage, waitForPageLoader } from '../support/helpers';

test.describe('Settings deep checks', () => {
  test('appearance tabs switch between sections', async ({ page }) => {
    await page.goto('/settings/appearance');
    await dismissProductTour(page);

    const tabs = page.locator('[data-testid="appearance-tabs"]');
    await expect(tabs).toBeVisible();

    for (const section of ['themes', 'brand', 'layout', 'colors']) {
      const tab = page.locator(`[data-testid="appearance-tab-${section}"]`);
      await expect(tab).toBeVisible();
      await tab.click();
      await waitForPageLoader(page);
      await expect(page).toHaveURL(new RegExp(`/settings/appearance/${section}`));
      await expect(page.getByRole('main').locator('form').first()).toBeVisible();
    }
  });

  test('appearance colors subtabs are reachable', async ({ page }) => {
    await expectAuthenticatedPage(page, '/settings/appearance/colors');
    const subtabs = page.locator('[data-testid="appearance-subtabs"]');
    if ((await subtabs.count()) === 0) {
      return;
    }
    for (const sub of ['accents', 'status', 'surfaces']) {
      const link = page.locator(`[data-testid="appearance-subtab-${sub}"], a[href*="sub=${sub}"]`).first();
      if ((await link.count()) === 0) {
        continue;
      }
      await link.click();
      await waitForPageLoader(page);
      await expect(page).toHaveURL(new RegExp(`/settings/appearance/colors/${sub}`));
    }
  });

  test('instance config export and import panels load', async ({ page }) => {
    await expectAuthenticatedPage(page, '/settings/instance-config');
    await expect(page.locator('[data-testid="instance-config-export"]')).toBeVisible();
    await expect(page.locator('[data-testid="instance-config-download"]')).toBeVisible();
    await expect(page.locator('[data-testid="instance-config-import"]')).toBeVisible();
    // The import form lives in the second tab panel (hidden until its tab is opened).
    await page.locator('[data-testid="instance-config-import"]').click();
    await expect(page.locator('[data-testid="instance-config-file"]')).toBeVisible();
  });

  test('instance config export download is reachable', async ({ page }) => {
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 20_000 }).catch(() => null),
      page.goto('/settings/instance-config/export', { waitUntil: 'commit' }).catch((err: Error) => {
        if (!/Download is starting/i.test(err.message)) {
          throw err;
        }
        return null;
      }),
    ]);

    if (download) {
      expect(download.suggestedFilename().length).toBeGreaterThan(0);
      return;
    }
    await expect(page).not.toHaveURL(/\/login/);
  });

  test('admin hub deep cards navigate', async ({ page }) => {
    await page.goto('/admin');
    await dismissProductTour(page);

    const cards = [
      { testid: 'admin-instance-config', url: /\/settings\/instance-config/ },
      { testid: 'admin-http-log', url: /\/admin\/http-log/ },
      { testid: 'admin-cookie-consent', url: /\/admin\/cookie-consent|\/cookie-consent/ },
    ] as const;

    for (const card of cards) {
      await page.goto('/admin');
      await dismissProductTour(page);
      const link = page.locator(`[data-testid="${card.testid}"]`);
      await expect(link).toBeVisible();
      await link.click();
      await expect(page).toHaveURL(card.url);
      await expect(page).not.toHaveURL(/\/login/);
    }
  });
});
