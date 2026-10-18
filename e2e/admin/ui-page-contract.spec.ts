import { expect, test } from '@playwright/test';
import { dismissCookieConsent, dismissProductTour, expectAuthenticatedPage } from '../support/helpers';

/**
 * UiKit 2 page contract (REQ-UI-003): kit pages in the app bridges (layouts/ui_admin.html.twig, ui_guest.html.twig),
 * painted by nowo_ui_kit.theme, UiKit runtimes under the CSP nonce.
 */
test.describe('UiKit page contract', () => {
  test.describe('guest bridge', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('sign-in is the auth scaffold in the guest shell, password toggle works', async ({ page }) => {
      await page.goto('/login');
      await dismissCookieConsent(page);

      const panel = page.locator('section[data-nowo-ui-page="auth"]');
      await expect(panel).toBeVisible();
      await expect(page.locator('.nowo-ui-host-page--auth[data-nowo-ui-bundle="NowoAuthKitBundle"]')).toHaveCount(1);
      await expect(page.locator('.brand-mark').first()).toBeVisible();

      // The eye toggle is a Stimulus controller: it only works when the CSP nonce reaches the scripts.
      const password = page.locator('input[name="login_form[_password]"]');
      await expect(password).toHaveAttribute('type', 'password');
      await page.locator('.form-password-toggle .input-group-text, .form-password-toggle [data-controller~="password-toggle"]').first().click();
      await expect(password).toHaveAttribute('type', 'text');
    });

    test('invalid credentials show the scaffold alert', async ({ page }) => {
      await page.goto('/login');
      await dismissCookieConsent(page);
      await page.locator('input[name="login_form[_username]"]').fill('nobody@nowo-shell.local');
      await page.locator('input[name="login_form[_password]"]').fill('wrong-password-123');
      await page.locator('main [data-nowo-ui-page="auth"] button[type="submit"]').first().click();
      await expect(page.locator('[data-nowo-ui-page="auth"] [role="alert"]').first()).toBeVisible();
    });

    test('maintenance preview is the bundle page with the 503 art and no session chrome', async ({ page }) => {
      const response = await page.goto('/_maintenance_preview');
      expect(response).not.toBeNull();
      expect([200, 503]).toContain(response!.status());
      await expect(page.locator('.maintenance-page__figure img')).toBeVisible();
      await expect(page.locator('#cookieconsent')).toHaveCount(0);
    });
  });

  test('kit admin pages render in the admin bridge with the app theme', async ({ page }) => {
    for (const [path, bundle] of [
      ['/admin/menus/', 'NowoDashboardMenuBundle'],
      ['/breadcrumb-kit-admin/', 'NowoBreadcrumbKitBundle'],
      ['/admin/_routing/', 'NowoRoutingKitBundle'],
      ['/admin/maintenance/', 'NowoMaintenanceModeBundle'],
      ['/admin/http-log', 'NowoHttpLogBundle'],
    ] as const) {
      await expectAuthenticatedPage(page, path);
      await expect(page.locator(`.kit-admin[data-nowo-ui-bundle="${bundle}"]`), path).toBeVisible();
      await expect(page.locator('#app-sidebar'), path).toBeAttached();
      await expect(page.locator('.kit-admin-page-header__title').first(), path).toBeVisible();
    }
  });

  test('a kit delete opens the UiKit confirm dialog', async ({ page }) => {
    await page.goto('/breadcrumb-kit-admin/');
    await dismissProductTour(page);
    const deleteAction = page.locator('.kit-admin .nowo-ui-row-actions .btn-danger').first();
    if ((await deleteAction.count()) === 0) {
      test.skip(true, 'no breadcrumb collection to delete');
    }
    await deleteAction.click();
    const dialog = page.locator('dialog[open]');
    await expect(dialog).toBeVisible();
    // Cancel closes it without deleting.
    await dialog.locator('button[value="cancel"], [data-nowo-ui-dialog-close], button:has-text("Cancel")').first().click();
    await expect(page.locator('dialog[open]')).toHaveCount(0);
  });
});
