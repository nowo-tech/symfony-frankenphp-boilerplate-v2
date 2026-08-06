import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['assets/**/*.{test,spec}.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'html', 'lcov'],
      reportsDirectory: 'var/coverage-js',
      include: ['assets/**/*.{ts,js}'],
      exclude: [
        'assets/**/*.{test,spec}.ts',
        'assets/stimulus-env.d.ts',
        'assets/**/*.d.ts',
      ],
    },
  },
});
