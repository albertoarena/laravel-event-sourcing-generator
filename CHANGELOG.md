# Changelog

All notable changes to `laravel-event-sorucing-generator` will be documented in this file:

## Unreleased

### What's Changed

* Security (docs site only): consolidate eight separate Dependabot pull requests into a single lockfile resolution, clearing all 20 open advisories against the Astro website — including a critical Astro RCE via AVIF image optimization (fixed in 7.2.8) and 13 `undici` alerts (fixed in 8.10.2). Two of those advisories had no working fix among the open pull requests: `devalue` 5.9.2 had been overtaken by a widened advisory range (`<= 5.9.2`), and `http-cache-semantics` had no pull request at all. `website/` is excluded from the Composer `dist` and is never installed by consumers
* Chore: group Dependabot **security** updates per ecosystem with `applies-to: security-updates` — version updates and security updates are separate flows and only the former was grouped, which is why eight advisories opened eight pull requests against the same lock file, each forcing a rebase of the others
* Chore: pin the Dependabot schedule to Monday 06:00 `Europe/Rome`, lower `open-pull-requests-limit` to 5 (3 for Actions), move Actions to a monthly interval, and label each ecosystem
* Chore(deps): bump `@astrojs/starlight` to 0.42.4 and `astro` to 7.3.x; Starlight 0.42 rebuilds the mobile menu on the native Popover API, dropping support for Chromium < 116, Safari < 17 and Firefox < 125, and gaining a menu that works without JavaScript — the rendered documentation is otherwise unchanged
* Chore(deps): bump `laravel/framework` to 13.34.0 and `league/commonmark` to 2.10.3, clearing the three remaining `composer.lock` advisories (dev-only; `composer.lock` is excluded from the Composer `dist`)
* Chore(deps): bump `larastan/larastan`, `laravel/pint`, `phpstan/phpstan`, `orchestra/testbench` and `phpunit/phpunit` (dev-only)
* CI: bump `actions/deploy-pages` to 5.0.1 (SHA-pinned; adds backoff and jitter to deployment status polling)

Known: the grouped Composer update again resolved Symfony down to the 7.4 LTS line, as in v1.1.3. Symfony 8.x requires PHP `>=8.4.1` while Dependabot resolves against the `^8.3` floor, so this recurs on every grouped update; a `config.platform.php` pin would hold the lock at Symfony 8 but would break the PHP 8.3 leg of the test matrix, which runs `composer update` rather than `composer install`. One medium advisory (`postcss-selector-parser`, CVE-2026-104844) is knowingly left open: the fix is unreachable because `@expressive-code/core` pins `postcss-nested ^6.0.1`, and the parser only ever reads the project's own CSS at build time.

No runtime code changed: `src/`, `config/` and the `composer.json` constraints are identical to v1.1.3 — the Composer `dist` archive is byte-for-byte identical across all 81 files, so there is nothing here for consumers to upgrade to.

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.1.3...main

## 1.1.3 - 2026-09-01

### What's Changed

* Docs: add a "Stay updated" section to the README offering the free Spatie Event Sourcing cheat sheet (printable PDF)
* Docs: add the [Why PHP](https://whyphp.dev) community badge
* Chore: retire the repo-views traffic badge, its daily workflow and the `TRAFFIC_TOKEN` secret; the accumulated counts were archived before the `traffic-data` branch was deleted
* Chore(deps): bump `orchestra/testbench`, `phpstan/phpstan`, `phpunit/phpunit` and `league/commonmark` (dev-only; `composer.lock` is excluded from the Composer `dist`)
* Chore(deps): regenerate `composer.lock` on PHP 8.4 to restore Symfony 8.1.x, which a grouped Dependabot update had resolved down to the 7.4 LTS line against the `^8.3` PHP floor
* Security (docs site only): bump `nanoid` to 3.3.18 in the Astro website, resolving GHSA-2v37-7h3g-55p8; `website/` is excluded from the Composer `dist` and is never installed by consumers

No runtime code changed: `src/`, `config/` and the `composer.json` constraints are identical to v1.1.2, so consumers are unaffected — this release exists to publish the README updates.

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.1.2...v1.1.3

## 1.1.2 - 2026-08-05

### What's Changed

* Security: pin all GitHub Actions to full commit SHAs (with trailing version comments) to mitigate the tag-hijack supply-chain class demonstrated by the `tj-actions/changed-files` incident
* Security: add a Dependabot `cooldown` (7-day minimum age) for Composer, npm and GitHub Actions so a freshly published release cannot propagate instantly; group minor/patch bumps to reduce PR noise
* Security: add `SECURITY.md` documenting a private vulnerability disclosure policy
* Chore: add `scripts/plumb-scan.sh` to request a package quality re-scan (dev tooling, excluded from the Composer `dist`)

No runtime code changed: `src/` and the `composer.json` constraints are identical to v1.1.1, so consumers are unaffected — this release exists to publish the supply-chain hardening.

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.1.1...v1.1.2

## 1.1.1 - 2026-07-23

### What's Changed

* Chore: add `.gitattributes` with `export-ignore` rules so development-only files (CI config, docs, tests, website, tooling) are excluded from the Composer `dist` archive, dramatically shrinking the distributed package
* Chore: normalise line endings to LF via `* text=auto eol=lf`

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.1.0...v1.1.1

## 1.1.0 - 2026-06-30

### What's Changed

* Feature: add support for Laravel 12 and Laravel 13
* Feature: add support for PHP 8.5
* Breaking (dev/CI only): drop Laravel 10 from the test matrix (end-of-life since February 2025)
* Deprecation: Laravel 11 is retained as a supported floor for one release but is now deprecated (security support ended March 2026); a future release will remove it
* Chore: widen `orchestra/testbench` dev constraint to `^9 || ^10 || ^11` and expand the CI matrix to PHP 8.3/8.4/8.5 × Laravel 11/12/13
* Chore: bump `larastan/larastan` to `^3.0` and `phpstan/phpstan` to `^2.0` (required for Laravel 12/13 static analysis)
* Fix: harden `preg_replace`/`Str::replaceMatches` return handling in `StubReplacer` flagged by PHPStan 2

This clears the three `laravel/framework` security advisories surfaced by `composer audit`, which were unpatchable on the end-of-life Laravel 11 branch. The runtime `require` constraints are unchanged (`illuminate/contracts` / `illuminate/support`), so consumers on any supported Laravel are unaffected.

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.15...v1.1.0

## 1.0.15 - 2026-06-16

### What's Changed

* Fix: Slack notification label now honours the configured primary key (was hard-coded to `uuid`, so non-UUID models incorrectly displayed `uuid` in Slack messages)
* Chore: remove unused `tests/Mocks/MockFilesystem.php`
* Chore: remove `stopOnFailure="true"` from `phpunit.xml` so CI surfaces all regressions in a single run
* Chore: bump `guzzlehttp/*` transitive dev dependencies (Dependabot #18)
* Docs: add codebase review plan and unit-test scoping plan; document `composer audit` triage for transitive dev dependencies

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.14...v1.0.15

## 1.0.14 - 2026-05-06

### What's Changed

* Chore: upgrade PHPUnit from v11 to v12 and migrate configuration
* Chore: add Claude local settings to `.gitignore`

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.13...v1.0.14

## 1.0.13 - 2026-04-07

### What's Changed

* CI: update PHP versions to 8.3 and 8.4 (drop PHP 8.2)
* Security: upgrade PHPUnit to 11.5.50
* Docs: add CLAUDE.md guidance file, improve README structure and clarity
* Docs: add Packagist badges and fix broken anchors
* Chore: update dependencies

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.12...v1.0.13

## 1.0.12 - 2025-03-18

### What's Changed

* Migrations, bug fix: exclude down() method from being parsed

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.11...v1.0.12

## 1.0.11 - 2025-03-18

### What's Changed

* Migrations, support dropColumn and renameColumn
* Migrations, support excluded parameter

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.10...v1.0.11

## 1.0.10 - 2025-03-18

### What's Changed

* Composer update (Laravel 11.44.2)

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.9...v1.0.10

## 1.0.9 - 2025-03-16

### What's Changed

* Add database notifications

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.8...v1.0.9

## 1.0.8 - 2025-02-16

### What's Changed

* Support update migrations

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.7...v1.0.8

## 1.0.7 - 2024-12-31

### What's Changed

* Support PHP 8.3

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.6...v1.0.7

## 1.0.6 - 2024-12-21

### What's Changed

* Fix Slack notifications
* Improve stub asserts

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.5...v1.0.6

## 1.0.5 - 2024-12-21

### What's Changed

* Fix: do not add comments for Blueprint skipped methods

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.4...v1.0.5

## 1.0.4 - 2024-12-21

### What's Changed

* Improve documentation
* Add changelog
* Change indentation option
* Improve documentation. Add Contributing page.

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.3...v1.0.4

## 1.0.3 - 2024-12-20

### What's Changed

* Refactor aggregates to use Spatie folders
* Composer update

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.2...v1.0.3

## 1.0.2 - 2024-12-02

### What's Changed

* Fix namespace of generated unit tests

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.1...v1.0.2

## 1.0.1 - 2024-12-01

### What's Changed

* Infer if Carbon must be included in generated files

**Full Changelog**: https://github.com/albertoarena/laravel-event-sourcing-generator/compare/v1.0.0...v1.0.1

## 1.0.0 - 2024-11-25

### What's Changed

* first version!

