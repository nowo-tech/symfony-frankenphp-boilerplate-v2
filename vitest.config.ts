import { defineConfig } from 'vitest/config';

/**
 * REQ-QA-002 (TS): gate coverage on the includable unit-testable set only.
 * See docs/COVERAGE.md.
 */
export default defineConfig({
  test: {
    environment: 'jsdom',
    // Controllers only — thinking-orbs / assets/lib stay out of the suite (and out of the gate).
    include: [
      'assets/controllers/**/*.{test,spec}.ts',
    ],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      reportsDirectory: 'var/coverage-js',
      include: [
        'assets/controllers/clipboard_copy_controller.ts',
        'assets/controllers/collapse_panel_controller.ts',
        'assets/controllers/confirm_submit_controller.ts',
        'assets/controllers/human_key_label_controller.ts',
        'assets/controllers/navigate_select_controller.ts',
        'assets/controllers/password_toggle_controller.ts',
        'assets/controllers/tabs_controller.ts',
      ],
      exclude: [
        'assets/**/*.{test,spec}.ts',
        'assets/stimulus-env.d.ts',
        'assets/**/*.d.ts',
        'assets/app.ts',
        'assets/stimulus_bootstrap.ts',
        'assets/theme-boot.ts',
        'assets/controllers/combobox_controller.ts',
        'assets/controllers/confirm_dialog_controller.ts',
        'assets/controllers/csrf_protection_controller.ts',
        'assets/controllers/datatable_controller.ts',
        'assets/controllers/menu_nested_collapse_controller.ts',
        'assets/controllers/page_loader_controller.ts',
        'assets/controllers/password_confirm_mirror_controller.ts',
        'assets/controllers/product_tour_controller.ts',
        'assets/controllers/qr_login_controller.ts',
        'assets/controllers/thinking_orb_controller.ts',
        'assets/controllers/toast_stack_controller.ts',
        'assets/lib/**',
      ],
      thresholds: {
        lines: 100,
        statements: 100,
      },
    },
  },
});
