# Coverage Badge Refactoring

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** Approved — Option A; implementation deferred (do later)

_Minor infrastructure task — no Management Summary (per the plan guidelines)._

## Changelog

- **2026-07-01** — Initial draft.
- **2026-07-01** — Review: **Option A approved** (CI job + orphan `coverage-data` branch, with the pcov speed-up). Implementation deferred to a later session.

## Purpose

Replace the current manual, local-only coverage-badge process with something **more efficient and drift-free**, ideally generated in CI. Today `coverage.svg` is produced by hand via `composer test-coverage` and committed to `main`; it is stale (last regenerated 2026-02-15) and depends on Xdebug being installed locally.

## Current State Analysis

**Process** — `composer test-coverage` runs three chained steps:
1. `@putenv XDEBUG_MODE=coverage`
2. `phpunit --coverage-html reports/ --coverage-clover clover.xml --process-isolation tests`
3. `php-coverage-badger clover.xml coverage.svg`

**Facts:**
- `jaschilz/php-coverage-badger ^2.0` is a `require-dev` dependency; `vendor/bin/php-coverage-badger` is present.
- `reports/` and `clover.xml` are gitignored (`.gitignore:32-33`).
- **`coverage.svg` is committed to `main`** and referenced by the README badge: `![build-test](coverage.svg)` (line 3).
- The badge is **not** produced in CI — `test.yml` runs `composer run test` only. So `coverage.svg` is regenerated manually and committed by hand.

**Problems:**
1. **Drift** — the committed badge is ~5 months stale; nothing keeps it current.
2. **Local dependency** — requires the Xdebug extension; contributors without it silently produce a wrong/empty badge.
3. **Slow** — `--process-isolation` plus coverage instrumentation runs every test in its own process.
4. **Repo churn** — a binary-ish SVG committed to `main` on every coverage change.

**Existing precedent to reuse:** the repo already solves "auto-updated badge without committing to `main`" for traffic stats — `github-traffic-badge.yml` publishes to an orphan `traffic-data` branch, and the README references it by raw URL:
`![Repo views](https://raw.githubusercontent.com/albertoarena/laravel-event-sourcing-generator/traffic-data/badge.svg)` (line 8).

## Options

### Option A — CI job + orphan `coverage-data` branch (recommended)
Mirror the traffic-badge pattern. A GitHub Actions job runs coverage, generates the badge, and commits **only** `coverage.svg` to an orphan `coverage-data` branch; the README points at the raw URL.

- **Pros:** consistent with the existing traffic-badge approach; no third-party service or data sharing; removes the committed SVG from `main`; always current; keeps `php-coverage-badger`.
- **Cons:** an extra workflow to maintain; badge reflects the default branch only.
- **Speed:** use **pcov** instead of Xdebug in CI (much faster for line coverage) and drop `--process-isolation` unless a test genuinely needs it.

### Option B — Third-party coverage service (Codecov or Coveralls)
CI uploads `clover.xml`; the service hosts the badge and a coverage-report UI. README badge becomes a shields.io/Codecov badge.

- **Pros:** least workflow code; trend graphs, PR annotations, historical data; no badge stored anywhere in the repo.
- **Cons:** adds an external dependency and an upload token; sends coverage data to a third party; another account to own.

### Option C — Keep local, just speed it up
Leave generation local/manual but switch `XDEBUG_MODE=coverage` → pcov and drop `--process-isolation`.

- **Pros:** smallest change.
- **Cons:** does **not** fix drift or the local-Xdebug requirement — the core problems remain. Not recommended on its own.

## Recommendation

> **Decision: Option A approved.** Implementation is deferred to a later session — this plan is the record to pick up from.

**Option A**, because it kills the drift, removes the local-Xdebug requirement for the published badge, and reuses a pattern already proven in this repo (so the mental model and README convention stay consistent). Fold in the pcov speed-up from Option C for the CI run. Keep `composer test-coverage` available for local/ad-hoc use, but simplify it (pcov, reconsider `--process-isolation`) and stop tracking `coverage.svg` on `main`.

Option B is a reasonable alternative if richer coverage reporting (trends, PR comments) is wanted later; it can replace Option A without further repo changes beyond the README badge and a CI upload step.

## Steps (Option A)

1. **Add a coverage CI job** (extend `test.yml` or a new `coverage.yml`):
   - Single matrix cell (e.g. PHP 8.4, `prefer-stable`) with **pcov** enabled (`coverage: pcov` in `shivammathur/setup-php`).
   - Run `phpunit --coverage-clover clover.xml` (evaluate dropping `--process-isolation`).
   - Run `php-coverage-badger clover.xml coverage.svg`.
2. **Publish the badge to an orphan `coverage-data` branch** — commit only `coverage.svg` there (checkout-orphan + force-push pattern, or a small publish action), `contents: write` permission, `concurrency` guard. Trigger on push to `main` (and `workflow_dispatch`).
3. **Update the README badge** to the raw URL:
   `![coverage](https://raw.githubusercontent.com/albertoarena/laravel-event-sourcing-generator/coverage-data/coverage.svg)`.
4. **Stop tracking `coverage.svg` on `main`** — `git rm --cached coverage.svg`, add it to `.gitignore` (now permitted). Verify with `git check-ignore`.
5. **Simplify `composer test-coverage`** for local use — switch to pcov (`XDEBUG_MODE` no longer needed) and reconsider `--process-isolation`; keep `--coverage-html reports/` for local inspection.
6. **Docs** — note in `docs/ARCHITECTURE.md` (Testing) that the published badge is CI-generated on the `coverage-data` branch, and add the conditional line to `docs/CHECKLIST.md`: *"badge is produced in CI — no manual regeneration needed."*

## Verification

- CI job runs green and pushes an updated `coverage.svg` to `coverage-data`.
- README badge renders from the raw URL.
- `coverage.svg` no longer appears in `git status` on `main`; `git check-ignore coverage.svg` reports it ignored.
- `composer test-coverage` still works locally (with pcov) and writes `reports/`.

## References

- Current script: `composer.json` → `scripts.test-coverage`.
- Badge tool: `jaschilz/php-coverage-badger` (`require-dev`).
- Precedent: `.github/workflows/github-traffic-badge.yml` + README line 8 (orphan-branch badge via raw URL).
- README badge to change: `README.md:3`.
- CI setup: `.github/workflows/test.yml` (matrix + `shivammathur/setup-php`).

## Feedback

_(Reviewer comments below.)_
