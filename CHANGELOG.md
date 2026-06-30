# Changelog

All notable changes to `laravel-event-sorucing-generator` will be documented in this file:

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

