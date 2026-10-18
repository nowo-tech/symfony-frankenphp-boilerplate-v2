import { expect, test, type Page } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

export const DEMO_EMAIL = process.env.PLAYWRIGHT_DEMO_EMAIL ?? 'admin@nowo-shell.local';
export const DEMO_PASSWORD = process.env.PLAYWRIGHT_DEMO_PASSWORD ?? 'admin123';

/** Wait for the Nowo Shell page-loader overlay to release pointer events. */
export async function waitForPageLoader(page: Page): Promise<void> {
  const loader = page.locator('.page-loader.is-active, [data-controller="page-loader"].is-active');
  await loader.waitFor({ state: 'hidden', timeout: 15_000 }).catch(() => undefined);
}

/** Dismiss cookie consent only when the modal is actually open. */
export async function dismissCookieConsent(page: Page): Promise<void> {
  await waitForPageLoader(page);
  const openModal = page.locator('#cookieconsent[data-nowo-open="true"]:not(.hidden)');
  try {
    await openModal.waitFor({ state: 'visible', timeout: 3_000 });
  } catch {
    return;
  }

  const acceptAll = openModal.locator('#cookie_consent_use_all_cookies');
  const functionalOnly = openModal.locator('#cookie_consent_use_only_functional_cookies');

  const target = (await acceptAll.isVisible().catch(() => false)) ? acceptAll : functionalOnly;
  await target.waitFor({ state: 'visible', timeout: 5_000 });
  await expect(target).toBeEnabled();
  await target.evaluate((el: HTMLElement) => el.click());

  await openModal.waitFor({ state: 'hidden', timeout: 10_000 }).catch(async () => {
    // Fallback: set consent cookies so subsequent navigations skip the modal.
    await page.context().addCookies([
      { name: 'Cookie_Consent', value: 'true', url: page.url() },
      { name: 'Cookie_Consent_Key', value: 'e2e', url: page.url() },
    ]);
  });
}

/** Close driver.js product tour when present. */
export async function dismissProductTour(page: Page): Promise<void> {
  await waitForPageLoader(page);
  const popover = page.locator('.driver-popover, .app-driver-popover');
  try {
    await popover.first().waitFor({ state: 'visible', timeout: 2_000 });
  } catch {
    return;
  }

  for (let i = 0; i < 12; i++) {
    if (!(await popover.first().isVisible().catch(() => false))) {
      return;
    }
    const next = popover
      .locator(
        'button.driver-popover-next-btn, button.driver-popover-done-btn, button.driver-popover-close-btn, button:has-text("Done"), button:has-text("Skip"), button:has-text("Close"), button:has-text("Omitir"), button:has-text("Listo"), button:has-text("Siguiente"), button:has-text("Next")',
      )
      .first();
    if (await next.isVisible().catch(() => false)) {
      await next.click({ force: true }).catch(() => undefined);
      await popover.first().waitFor({ state: 'hidden', timeout: 500 }).catch(() => undefined);
      continue;
    }
    await page.keyboard.press('Escape').catch(() => undefined);
    await popover.first().waitFor({ state: 'hidden', timeout: 500 }).catch(() => undefined);
  }
}

/** Navigate with retries for transient WSL/Docker net::ERR_NETWORK_CHANGED / chrome-error. */
export async function gotoStable(page: Page, path: string, attempts = 5): Promise<void> {
  let lastError: unknown;
  for (let attempt = 0; attempt < attempts; attempt++) {
    try {
      await page.goto(path, { waitUntil: 'domcontentloaded' });
      const url = page.url();
      if (url.startsWith('chrome-error://') || url.startsWith('chrome-error:')) {
        throw new Error(`Navigation landed on chrome-error for ${path}`);
      }
      return;
    } catch (err) {
      lastError = err;
      const msg = String(err);
      const retryable =
        /ERR_NETWORK_CHANGED|net::ERR_|chrome-error|Navigation landed on chrome-error|Timeout/i.test(msg);
      if (attempt === attempts - 1 || !retryable) {
        throw err;
      }
      await page.waitForTimeout(400 * (attempt + 1));
    }
  }
  throw lastError;
}

export async function loginAsDemo(page: Page, email = DEMO_EMAIL, password = DEMO_PASSWORD): Promise<void> {
  await gotoStable(page, '/login');
  await dismissCookieConsent(page);

  await page.locator('input[name="login_form[_username]"]').fill(email);
  await page.locator('input[name="login_form[_password]"]').fill(password);
  await page.locator('.nowo-auth-kit__panel button[type="submit"], form[name="login_form"] button[type="submit"]').first().click();

  await page.waitForURL(/\/dashboard(\?|$)/, { timeout: 30_000 });
  await dismissProductTour(page);
}

/** Assert page did not land on login and returned a successful HTML response. */
export async function expectAuthenticatedPage(page: Page, path: string): Promise<void> {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  await dismissProductTour(page);
  expect(response, `No response for ${path}`).not.toBeNull();
  const status = response!.status();
  expect(status, `${path} returned ${status}`).toBeLessThan(400);
  await expect(page, `Expected auth for ${path}`).not.toHaveURL(/\/login(\?|$|\/)/);
  await expect(page.locator('body')).toBeVisible();
}

export async function expectGuestPage(page: Page, path: string): Promise<void> {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  await dismissCookieConsent(page);
  expect(response, `No response for ${path}`).not.toBeNull();
  const status = response!.status();
  expect(status, `${path} returned ${status}`).toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
}

export async function logout(page: Page): Promise<void> {
  const menu = page.locator('[data-controller="user-menu"], [data-user-menu]');
  await menu.locator('summary').click();
  const logoutLink = menu.locator('a[href*="/logout"]');
  await expect(logoutLink).toBeVisible();
  await logoutLink.click();
  await page.waitForURL(/\/login(\?|$|\/)/, { timeout: 20_000 });
}

/** CSS that strips Symfony/dev overlays so screenshots look production-like. */
export const MANUAL_DEV_CHROME_HIDE_CSS = `
  .sf-toolbar,
  [id^="sfwdt"],
  .sf-toolbar-clearer,
  .sf-minitoolbar,
  [id^="_twig_inspector"],
  ._twig_inspector__filter_highlight,
  vite-error-overlay,
  .flash-toast,
  .nowo-ui-toast,
  .nowo-ui-toast__container,
  .nowo-cookie-consent__preferences-bubble,
  #cookieconsent .nowo-cookie-consent__preferences-bubble {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }
`;

/**
 * Force English UI for documentation shots (manual is English; demo users may prefer es).
 */
export async function ensureEnglishUi(page: Page): Promise<void> {
  const htmlLang = (await page.locator('html').getAttribute('lang'))?.toLowerCase() ?? '';
  if (htmlLang === 'en' || htmlLang.startsWith('en-')) {
    return;
  }

  const switcher = page.locator('.locale-switcher').first();
  if (!(await switcher.isVisible().catch(() => false))) {
    return;
  }

  const details = switcher.locator('details.locale-switcher__details').first();
  const summary = switcher.locator('summary.locale-switcher__summary').first();
  if (await details.count()) {
    const open = await details.getAttribute('open');
    if (open === null && (await summary.isVisible().catch(() => false))) {
      await summary.click({ force: true }).catch(() => undefined);
    }
  }

  const enOption = page
    .locator(
      [
        'form[action*="/account/locale/en"] button.locale-switcher__option',
        'form[action*="/locale/en"] button.locale-switcher__option',
        'a.locale-switcher__option[hreflang="en"]',
        'button.locale-switcher__option[hreflang="en"]',
        'button.locale-switcher__option[lang="en"]',
      ].join(', '),
    )
    .first();

  if (!(await enOption.isVisible().catch(() => false))) {
    return;
  }

  await Promise.all([
    page.waitForLoadState('domcontentloaded').catch(() => undefined),
    enOption.click({ force: true }),
  ]);
  await waitForPageLoader(page);
}

/**
 * Prepare the page for documentation screenshots: dismiss product UX overlays
 * and hide development chrome (WDT, Twig Inspector, Vite error overlay, toasts).
 */
export async function prepareProductionScreenshot(page: Page): Promise<void> {
  await waitForPageLoader(page);
  await dismissCookieConsent(page);
  await dismissProductTour(page);
  await ensureEnglishUi(page);
  await dismissCookieConsent(page);
  await dismissProductTour(page);
  await page.addStyleTag({ content: MANUAL_DEV_CHROME_HIDE_CSS }).catch(() => undefined);
  await page
    .evaluate(() => {
      document
        .querySelectorAll(
          '.sf-toolbar, [id^="sfwdt"], .sf-toolbar-clearer, .sf-minitoolbar, [id^="_twig_inspector"], vite-error-overlay, .flash-toast, .nowo-ui-toast, .nowo-ui-toast__container, .nowo-cookie-consent__preferences-bubble',
        )
        .forEach((el) => el.remove());
    })
    .catch(() => undefined);
  await page.waitForTimeout(150);
}

/** Fixed documentation viewport — never fullPage (keeps aside/chrome aspect ratio). */
export const MANUAL_VIEWPORT = { width: 1440, height: 900 } as const;

export type ManualTheme = 'light' | 'dark';

/**
 * Force day/night theme for screenshots (overrides user preference + localStorage).
 * Shell uses `app-theme` in localStorage and `data-theme` on `<html>` (see assets/theme-boot.ts).
 */
export async function setManualTheme(page: Page, theme: ManualTheme): Promise<void> {
  try {
    await page.evaluate((t) => {
      localStorage.setItem('app-theme', t);
      const root = document.documentElement;
      root.dataset.theme = t;
      root.dataset.userTheme = t;
      window.__APP_USER_THEME__ = t;
    }, theme);
  } catch {
    // about:blank / opaque origins deny localStorage — caller should navigate first.
    return;
  }
  await page.waitForTimeout(100);
}

export type CaptureManualOptions = {
  /** Default light. Dark shots are saved as `{name}-dark.png`. */
  theme?: ManualTheme;
  /** Extra same-size scrolls for tall pages (default 1 = only above-the-fold). Max 3. */
  maxParts?: number;
};

/**
 * Capture fixed-size viewport PNGs (1440×900). Tall pages get `-2` / `-3` companions
 * of the same dimensions after scrolling — never a stretched full-page image.
 */
export async function captureManualScreenshot(
  page: Page,
  outDir: string,
  name: string,
  options: CaptureManualOptions = {},
): Promise<void> {
  const theme = options.theme ?? 'light';
  const maxParts = Math.min(Math.max(options.maxParts ?? 1, 1), 3);
  await setManualTheme(page, theme);
  await prepareProductionScreenshot(page);
  await setManualTheme(page, theme);
  await page.setViewportSize(MANUAL_VIEWPORT);
  fs.mkdirSync(outDir, { recursive: true });

  const baseName = theme === 'dark' ? `${name}-dark` : name;
  const viewportHeight = MANUAL_VIEWPORT.height;

  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(80);

  for (let part = 1; part <= maxParts; part++) {
    if (part > 1) {
      await page.evaluate((y) => window.scrollTo(0, y), (part - 1) * viewportHeight);
      await page.waitForTimeout(120);
    }
    const scrollHeight = await page.evaluate(() =>
      Math.max(document.documentElement.scrollHeight, document.body?.scrollHeight ?? 0),
    );
    const maxScroll = Math.max(0, scrollHeight - viewportHeight);
    const at = (part - 1) * viewportHeight;
    if (part > 1 && at > maxScroll + 40) {
      break;
    }
    const fileName = part === 1 ? `${baseName}.png` : `${baseName}-${part}.png`;
    await page.screenshot({
      path: path.join(outDir, fileName),
      fullPage: false,
      animations: 'disabled',
      caret: 'hide',
    });
  }
  await page.evaluate(() => window.scrollTo(0, 0));
}
