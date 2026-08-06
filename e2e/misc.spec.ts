import { test, expect } from '@playwright/test';
import { dismissProductTour } from './helpers';

test.describe('Misc shell', () => {
  test('health live is public', async ({ request }) => {
    const res = await request.get('/health/live');
    expect(res.status()).toBe(200);
  });

  test('dashboard home after auth', async ({ page }) => {
    await page.goto('/dashboard');
    await dismissProductTour(page);
    await expect(page).not.toHaveURL(/\/login/);
  });
});
