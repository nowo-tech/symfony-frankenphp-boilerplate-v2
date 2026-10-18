# Coverage (REQ-QA-002)

Measured **2026-08-14** against includable `src/` (PHPUnit Clover statements) and includable Stimulus controllers (Vitest V8).

## Current measured floors

| Surface | Metric | Measured | CI / local gate |
|---------|--------|----------|-----------------|
| PHP (`src/`, Controllers + Demo excluded) | Statements (Clover) | **100%** | `COVERAGE_MIN=100` hard fail |
| TypeScript (includable Stimulus helpers) | Lines / statements (V8) | **100%** | Vitest `coverage.thresholds` lines/statements **100** |

Regenerate PHP:

```bash
make ensure-up
COVERAGE_MIN=0 make test-coverage
# → var/coverage/clover.xml + var/coverage-html/
```

Regenerate TS:

```bash
make test-unit-js-coverage
# → var/coverage-js/
```

## PHP source exclusions (`phpunit.dist.xml`)

| Path | Justification |
|------|----------------|
| `src/Identity/Controller/**`, `src/Shared/*/Controller/**`, `BarePublicLocaleRedirectController` | HTTP controllers exercised by Playwright e2e (REQ-QA-003) and Functional controller tests. Hard gate focuses on domain services, forms, entities, subscribers, commands. |
| `src/Setup/Demo/**` | Platform seeders for install tooling (`make seed-platform`). |
| `src/Identity/Service/DemoIdentitySeeder.php` | Demo-only identity seeder. |
| `src/Identity/Command/SeedDemoCommand.php` | Demo install CLI (`app:seed-demo`); same class of tooling as Demo seeders. |

## `@codeCoverageIgnore` (narrow)

| Location | Why |
|----------|-----|
| `Permission::__construct()` | Empty private constructor only blocks instantiation. |

## Test helpers for hard-to-reach FS edges

| Helper | Why |
|--------|-----|
| `tests/Support/JsonUploadReaderFsOverrides.php` (loaded from `tests/bootstrap.php`) | Namespace overrides for `is_readable` / `file_get_contents` so unit tests can force `missing_file` when the container runs as root (chmod 000 stays readable). |

## TypeScript includable set (`vitest.config.ts`)

Unit-tested Stimulus helpers under jsdom (`assets/controllers/**/*.{test,spec}.ts` only). Coverage `include` is the seven helpers below; complex QR / tour / DataTables / Three.js (`assets/lib/**`, including thinking-orbs) stay excluded from both the suite include and the coverage gate (Playwright / browser).

| Includable controller | Notes |
|----------------------|-------|
| `clipboard_copy_controller.ts` | Clipboard + URL fetch + fallback |
| `collapse_panel_controller.ts` | Panel open state + localStorage |
| `confirm_submit_controller.ts` | CSP-safe `window.confirm` |
| `human_key_label_controller.ts` | API key label generator |
| `navigate_select_controller.ts` | Select → `location.assign` |
| `password_toggle_controller.ts` | Show/hide password |
| `tabs_controller.ts` | Tab switcher |

## Gate policy

1. **PHP:** Includable `src/` statement coverage at **100%**; `COVERAGE_MIN=100` in Makefile + CI.
2. **TS:** Includable Stimulus helpers at **100%** lines/statements; Vitest thresholds **100**.
3. Never drop below the documented floor in CI.

## Related

- QA requirement: REQ-QA-002
- Script: `.scripts/check-coverage-threshold.sh`
- Dev helper: `dg/bypass-finals` enabled in `tests/bootstrap.php` so PHPUnit can double `final` seeders/commands used by unit coverage.
