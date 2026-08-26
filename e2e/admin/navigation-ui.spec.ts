import { test, expect } from '@playwright/test';
import { dismissProductTour } from '../support/helpers';

test.describe('Navigation UI', () => {
  test('dashboard home loads', async ({ page }) => {
    await page.goto('/dashboard');
    await dismissProductTour(page);
    await expect(page).not.toHaveURL(/\/login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('preferences and admin area switch', async ({ page }) => {
    await page.goto('/account/profile');
    await dismissProductTour(page);
    await expect(page).toHaveURL(/\/account\/profile/);

    await page.goto('/admin');
    await dismissProductTour(page);
    await expect(page).toHaveURL(/\/admin/);
  });
});
