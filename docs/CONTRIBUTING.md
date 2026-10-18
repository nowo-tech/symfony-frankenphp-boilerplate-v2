# Contributing

1. Follow Spec-Driven Development ([GitHub Spec Kit](https://github.com/github/spec-kit); see `.specify/memory/constitution.md`).
2. Open or update a feature under `specs/NNN-name/` before large changes (index: [`specs/README.md`](../specs/README.md)).
3. Use English for committed `*.md`, PHPDoc, and Cursor rules (UI strings in YAML/XLIFF).
4. Prefer small, reviewable PRs.
5. Run `make qa` before opening a PR when Docker is available.
6. Do not commit `.env`, Halite keys, or secrets.
7. Read [docs/ARCHITECTURE.md](ARCHITECTURE.md) before structural changes. This repo is the **platform boilerplate** (`Identity`, `Shared`, `Setup` only). Product domains belong in product apps built from this starter, not here.
8. Install git hooks: `make setup-hooks` (strips Cursor co-author trailers).
9. **Cursor Agent chat (REQ-IDE-APP-001)** — submit the agent prompt with **Ctrl+Enter** (not Enter alone). In Cursor Settings → Agents, set **Queue messages** / `queueMessageDefaultBehavior` to **`queue`** so follow-ups wait instead of interrupting a running turn.
10. Verify Spec Kit locally with `make specify-check` when changing `.specify/` or Cursor skills.
11. **Platform checkboxes** — native checks use class `checkbox` (or `.checkbox-label` / `.nowo-ui-check`); FormKit `field_types.checkbox.attr.class: checkbox` (`086-platform-form-chrome`).
**FormKit profiles** — platform host forms extend `App\Shared\Form\FormKitAbstractType` (profile `shell`). GET / list filters extend `App\Shared\Form\AbstractGetFilterType` (profile `filter`, `#[FormKitConfig('filter')]`, CSRF off, method GET). Do not mix: filter types must not extend `FormKitAbstractType` directly, and host forms must not carry `#[FormKitConfig('filter')]`. See `config/packages/nowo_form_kit.yaml`.
**`getBlockPrefix()`** — all host and filter form types must have a non-empty `getBlockPrefix()`. CSRF-only kit types (`Nowo\FormKitBundle\Form\Type\CsrfOnlyType`, `HiddenFieldsCsrfType`) intentionally use empty `''` (no catalogue keys).
12. **Password toggle** — host SCSS owns flex + gap (eye on the right); `nowo_password_toggle.button_classes` include `input-group-text` (no Tailwind flex/gap on the container).
13. **CSRF via Symfony Forms** — host mutable HTML POSTs use kit `CsrfOnlyFormFactory` / Twig `csrf_action_form()` / `search_query_form()` / `flat_hidden_fields()` (FormKit **≥2.4**) with `form_start` / `form_end`. Prefer factory `createNamed()` for nested `csrf_only[_token]`; use `create()` / `named: false` only when the consumer must see a flat `_token`. Do not add new hand-rolled Twig `csrf_token('…')` hidden fields for product/admin host forms. **Intentional exceptions** (keep `csrf_token()` / header tokens):
    - AJAX `data-*-token` / `X-CSRF-TOKEN` (theme, content-width, product-tour mark in `base.html.twig` / `_product_tour.html.twig`): product tour mark-seen at `/account/product-tour/seen` sends `X-CSRF-TOKEN: csrf_token('account_product_tour')` because the driver.js controller runs outside a Symfony form. This is a one-way AJAX write with SameSite protection — acceptable for low-risk UI state.
    - AuthKit logout `_csrf_token` (`_user_menu.html.twig`)
    - Kit modal `data-token` deletes (DashboardMenu, BreadcrumbKit overrides)
    - CookieConsent kit admin table fork (`templates/bundles/NowoCookieConsentBundle/…`)
    - HttpLog kit fork export/purge/delete (`templates/bundles/NowoHttpLogBundle/…` — leave until kit ships FormKit CSRF)

## QA gates

Run `make qa` (Docker) or `composer qa` (local) before opening a PR. This runs CS-fix, Twig-CS, PHPStan, Rector, module-boundary check, and unit tests.

| Gate | Command | Hard floor |
|------|---------|-----------|
| PHP unit tests + coverage | `make test-coverage` | **100%** statements (`COVERAGE_MIN`) |
| TypeScript unit coverage | `make test-unit-js-coverage` | **100%** lines/statements |
| E2E (Playwright) | `make test-e2e` | Informational on PR; required before release |
| Module boundaries | `make check-module-boundaries` | Always required |

Coverage floors are enforced by `.scripts/check-coverage-threshold.sh` and the CI matrix. Do not bypass with `COVERAGE_MIN=0` on PRs.

### E2E CI hardening (REQ-QA-006)

Warm product Playwright (`chromium`) **ignores** `e2e/worker/`, `e2e/cold/`, and `e2e/manual/` via `playwright.config.ts`. Those lanes stay on dedicated targets — not in the warm product gate:

| REQ | Lane | Make target |
| --- | ---- | ----------- |
| QA-010 | FrankenPHP worker Kernel isolation | `make test-e2e-worker-safe` |
| QA-011 | Cold-start / clean install | `make test-e2e-cold` / `make test-e2e-clean` |

Local sharding when debugging long runs: `make test-e2e-isolated ARGS='--shard=1/2'` (dogfood: `make test-e2e ARGS='--shard=1/2'`). CI keeps a **single** Playwright job while the suite is small. Details: [`e2e/README.md`](../e2e/README.md).

## Spec Kit workflow

| Step | Skill | Output |
|------|-------|--------|
| Principles | `/speckit-constitution` | `.specify/memory/constitution.md` |
| Specify | `/speckit-specify` | `specs/NNN-*/spec.md` |
| Clarify | `/speckit-clarify` | updates to spec |
| Plan | `/speckit-plan` | `plan.md` (+ research/contracts as needed) |
| Tasks | `/speckit-tasks` | `tasks.md` |
| Implement | `/speckit-implement` | code + tests |
| Converge | `/speckit-converge` | close gaps |

Integration: `cursor-agent` (skills in `.cursor/skills/`). Upgrade: `specify integration upgrade --force --script sh`.

## AI co-author trailers

Do **not** add AI co-author trailers to commit messages: neither `Co-authored-by: Cursor` / `cursoragent@cursor.com` nor `Co-Authored-By: Claude …` / `noreply@anthropic.com` (REQ-GIT-001). Run `make setup-hooks` so the `commit-msg` hook strips them.
