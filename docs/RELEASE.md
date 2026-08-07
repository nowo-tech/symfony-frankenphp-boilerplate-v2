# Release checklist

Use this checklist when cutting a new version of [symfony-frankenphp-boilerplate-v2](https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2) (Nowo Shell).

## Before tagging

1. **CHANGELOG.md**
   - Move `[Unreleased]` entries into `## [X.Y.Z] - YYYY-MM-DD`.
   - Leave an empty `## [Unreleased]` section at the top.
   - Update compare links at the bottom of the file.

2. **UPGRADING.md**
   - Document user-facing steps from the previous tag to `X.Y.Z`.
   - Keep first-install instructions accurate.

3. **README / composer.json**
   - Keep the GitHub About description aligned with `composer.json` `"description"`.
   - Ensure `homepage` / `support` URLs point at this repository.

4. **QA**
   - Locally: `make qa` (or CI on the release PR).
   - Confirm `.env` is **not** staged (`git status`).

5. **Commit**
   - Commit docs and any release-related fixes on `main`.
   - Do **not** add Cursor / `@cursor.com` co-author trailers (enable `.githooks` via `core.hooksPath`).

## Tag and push

```bash
git checkout main
git pull origin main
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin main
git push origin vX.Y.Z
```

Example for a patch release:

```bash
git tag -a v1.0.3 -m "Release v1.0.3"
git push origin v1.0.3
```

## GitHub Release

Create a release from the tag (UI or CLI):

```bash
gh release create vX.Y.Z --title "vX.Y.Z" --notes-file - <<'EOF'
Paste the matching ## [X.Y.Z] section from docs/CHANGELOG.md here.
EOF
```

Prefer pasting the matching `## [X.Y.Z]` section from [`CHANGELOG.md`](CHANGELOG.md) as the release body.

Optional: workflow [`.github/workflows/release.yml`](../.github/workflows/release.yml) automates release creation on `v*` tag push (`softprops/action-gh-release@v3`, Node 24). It is **idempotent**: if a release for that tag already exists (e.g. created with `gh release create`), the job succeeds and skips.

## After release

- Verify https://github.com/nowo-tech/symfony-frankenphp-boilerplate-v2/releases
- Confirm CI is green on the tag / `main`
- Confirm GitHub About **description** and **topics** match `composer.json` / this checklist
- Announce briefly (org / Discord / etc.) if applicable
