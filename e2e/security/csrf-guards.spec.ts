import { test, expect } from '@playwright/test';

/**
 * CSRF / auth gate smoke — anonymous mutation attempts must not succeed silently.
 */
test.describe('Security — CSRF guards', () => {
  test('guest POST to admin user create is rejected', async ({ request }) => {
    const res = await request.post('/admin/users/new', {
      form: { 'admin_user[_token]': 'invalid-guest-token' },
      failOnStatusCode: false,
      maxRedirects: 0,
    });
    expect(res.status(), await res.text()).toBeLessThan(500);
    expect([302, 401, 403, 405, 419, 422]).toContain(res.status());
    if (res.status() === 302) {
      expect(res.headers().location ?? '').toMatch(/login/i);
    }
  });
});
