# Contributing

1. Follow Spec-Driven Development ([GitHub Spec Kit](https://github.com/github/spec-kit); see `.specify/memory/constitution.md`).
2. Open or update a feature under `specs/NNN-name/` before large changes (index: [`specs/README.md`](../specs/README.md)).
3. Use English for committed `*.md`, PHPDoc, and Cursor rules (UI strings in YAML/XLIFF).
4. Prefer small, reviewable PRs.
5. Run `make qa` before opening a PR when Docker is available.
6. Do not commit `.env`, Halite keys, or secrets.
7. Read [docs/ARCHITECTURE.md](ARCHITECTURE.md) before structural changes. This repo is the **platform boilerplate** (`Identity`, `Shared`, `Setup` only). Product domains belong in product apps built from this starter, not here.
8. Install git hooks: `make setup-hooks` (strips Cursor co-author trailers).
9. Verify Spec Kit locally with `make specify-check` when changing `.specify/` or Cursor skills.

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
