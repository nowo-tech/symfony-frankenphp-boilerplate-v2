/**
 * Setup wizard screenshots for docs/manual/ (cold disposable stack only).
 * Day theme + English only (theme/locale demos live in prefs-* product shots).
 *
 * Run: make docs-manual-screenshots-setup
 * Prereq: make wipe-e2e-cold && make up-e2e-cold
 */
import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import {
  captureManualScreenshot,
  dismissCookieConsent,
  dismissProductTour,
  gotoStable,
  MANUAL_VIEWPORT,
} from '../support/helpers';
import { inspectManualPage } from '../support/manual-page-gate';
import {
  COLD_ADMIN_EMAIL,
  COLD_ADMIN_PASSWORD,
  COLD_SETUP_TOKEN,
  getSetupProgress,
  postSetupAdvance,
  runFreshInstallCircuit,
} from '../support/setup-cold';

const rootDir = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const outDir = path.join(rootDir, 'docs', 'manual', 'images');

async function shootSetup(
  page: import('@playwright/test').Page,
  name: string,
  expectedPath?: string,
  maxParts = 1,
): Promise<void> {
  const gate = await inspectManualPage(page, { expectedPath, allowAuthGate: true });
  if (!gate.ok) {
    test.info().annotations.push({
      type: 'skip-shot',
      description: `${name} skipped — ${gate.reason ?? 'page gate'}`,
    });
    try {
      fs.unlinkSync(path.join(outDir, `${name}.webp`));
    } catch {
      /* missing is fine */
    }
    return;
  }
  if (gate.kind === 'error-page') {
    test.info().annotations.push({
      type: 'error-shot',
      description: `${name} captured application error — ${gate.reason ?? '500'}`,
    });
  }
  await captureManualScreenshot(page, outDir, name, { theme: 'light', maxParts });
}

test.describe.configure({ mode: 'serial' });

test.describe('Setup wizard manual screenshots', () => {
  test.use({
    storageState: { cookies: [], origins: [] },
    viewport: { ...MANUAL_VIEWPORT },
  });

  test('capture setup gate and wizard steps', async ({ page, request }) => {
    await page.setViewportSize(MANUAL_VIEWPORT);

    // Prefer /setup directly — cold stacks may not redirect /login→/setup until
    // SiteBackup markers + empty schema are both consistent in the worker.
    // Prefer /login → setup redirect so the visitor-facing gate is painted.
    await gotoStable(page, '/login');
    await dismissCookieConsent(page);
    if (!/\/setup(\?|$|\/)/.test(page.url())) {
      await gotoStable(page, '/setup');
      await dismissCookieConsent(page);
    }
    await expect(page).toHaveURL(/\/setup(\?|$|\/)/);
    await page
      .locator('body')
      .getByText(/setup|token|first run|SITE_SETUP_TOKEN|Continue/i)
      .first()
      .waitFor({ state: 'visible', timeout: 15_000 })
      .catch(() => undefined);
    await shootSetup(page, 'setup-gate', '/setup', 1);

    await gotoStable(page, `/setup?token=${encodeURIComponent(COLD_SETUP_TOKEN)}`);
    await dismissCookieConsent(page);
    await dismissProductTour(page);
    await shootSetup(page, 'setup-wizard', '/setup', 2);

    let progress = await postSetupAdvance(request, {
      profile: 'fresh_install',
      bootstrap_mode: 'guided',
    });
    for (let i = 0; i < 25 && progress.phase !== 'failed' && progress.phase !== 'completed'; i++) {
      await gotoStable(page, `/setup?token=${encodeURIComponent(COLD_SETUP_TOKEN)}`);
      await dismissCookieConsent(page);
      const step = (progress.current_step_id ?? progress.phase ?? 'step').replace(/[^a-z0-9_-]+/gi, '-').toLowerCase();

      if (/admin/i.test(progress.current_step_id ?? '') || /admin/i.test(progress.message ?? '')) {
        await shootSetup(page, 'setup-admin', '/setup', 2);
        progress = await postSetupAdvance(request, {
          email: COLD_ADMIN_EMAIL,
          password: COLD_ADMIN_PASSWORD,
        });
        break;
      }
      if (progress.phase === 'waiting_input' && /sample/i.test(progress.current_step_id ?? '')) {
        await shootSetup(page, 'setup-sample', '/setup', 1);
        progress = await postSetupAdvance(request, { action: 'skip' });
        continue;
      }
      if (progress.phase === 'waiting_input' && /database_url/i.test(progress.current_step_id ?? '')) {
        progress = await postSetupAdvance(request, { action: 'skip' });
        continue;
      }
      if (progress.phase === 'waiting_input' && /bootstrap/i.test(progress.current_step_id ?? '')) {
        progress = await postSetupAdvance(request, { bootstrap_mode: 'guided' });
        continue;
      }
      if (i === 2) {
        await shootSetup(page, `setup-progress-${step}`.slice(0, 48), '/setup', 1);
      }
      progress = await postSetupAdvance(request, {});
    }

    const before = await getSetupProgress(request);
    if (!('redirectedHome' in before) && before.phase !== 'completed') {
      await runFreshInstallCircuit(request);
    }

    await gotoStable(page, '/setup/done');
    await dismissCookieConsent(page);
    if (page.url().includes('/setup')) {
      await shootSetup(page, 'setup-done', '/setup/done', 1);
    }
  });
});
