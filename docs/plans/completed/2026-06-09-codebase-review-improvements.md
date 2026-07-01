# Codebase Review: Improvements & Recommendations

**Date:** 2026-06-09
**Scope:** Full codebase audit — `src/`, `tests/`, `stubs/`, `config/`, `docs/`
**Status:** ✅ Completed (round concluded 2026-06-16) — see the Status table below: actionable items done, the rest deliberately postponed/closed. Item #9 lives on in its own plan (`2026-06-16-direct-unit-tests-for-core-classes.md`).

## Changelog

- 2026-06-09: Initial investigation
- 2026-06-16: Verified findings against codebase; addressed Bug #1 (Slack stub hard-coded `uuid`) via TDD; flagged Bug #2's proposed fix as incorrect (would break `--indentation` because stub files contain literal 4-space indents relied on by global `replaceIndentation()` pass); deleted unused `MockFilesystem.php` (#3) and removed `stopOnFailure` from `phpunit.xml` (#10); investigated #11 — non-bug (`array_filter` correctly drops empty string from `--notifications=`); investigated #8 — review claim was inaccurate, `isReservedName()` from `GeneratorCommand` is used at three call sites, change reverted; scoped #9 into a dedicated plan at `.claude/plans/alberto/2026-06-16-direct-unit-tests-for-core-classes.md`; postponed all remaining refactor/cleanup items (#2, #4, #5, #6, #7) — they will not be done in this round.

## Status

| # | Item | Status |
|---|------|--------|
| 1 | Slack notification hard-coded `uuid` | **Done** (2026-06-16) |
| 2 | `getIndentSpace()` ignores `--indentation` | **Postponed** — proposed fix would break `--indentation` (stub files contain literal 4-space indents relied on by the global `replaceIndentation()` pass); proper fix requires tokenising indentation in every stub file. Not in scope now. |
| 3 | Delete unused `MockFilesystem.php` | **Done** (2026-06-16) |
| 4 | Gitignore `coverage.svg`, `clover.xml` | **Postponed** — touches `.gitignore`, which CLAUDE.md guards against. Revisit only with explicit override. |
| 5 | `CommandSettings` builder pattern | **Postponed** — judgment-call refactor; no concrete pain point driving it. |
| 6 | Split `AssertsDomainGenerated` | **Postponed** — judgment-call refactor; revisit if stub set grows. |
| 7 | `HasBlueprintColumnType` → service | **Postponed** — style preference; no functional gain. |
| 8 | Extend `Command` instead of `GeneratorCommand` | **Not viable** — review claim was inaccurate. `isReservedName()` from `GeneratorCommand` is used at three call sites (lines 287, 294, 301). Reverted after test failures. |
| 9 | Add unit tests for core classes | **Scoped to own plan** — see `docs/plans/2026-06-16-direct-unit-tests-for-core-classes.md` |
| 10 | Remove `stopOnFailure` from `phpunit.xml` | **Done** (2026-06-16) |
| 11 | `--notifications` empty string edge case | **Non-bug** — verified empirically: `array_filter` with the `in_array(..., ACCEPTED)` predicate correctly drops the empty string from `explode(',', '')`. Function returns `[]`, which all downstream consumers handle as "no notifications". |

---

## Bugs

### 1. Slack notification concern hard-codes `uuid`

**File:** `stubs/notifications.concerns.has_slack_notification.stub:16`  
**File:** `tests/Concerns/AssertsDomainGenerated.php:249`

The Slack notification stub uses a literal `uuid` string instead of the `{{ primary_key }}` template variable:

```php
$block->field("*uuid:* $this->{{ id }}{{ primary_key:uppercase }}")->markdown();
```

When the primary key is `id` (non-UUID models), the notification will still display "uuid" in the Slack message. The test assertion at `AssertsDomainGenerated.php:249` also hard-codes `uuid`, so the bug isn't caught.

**Fix:** Replace `uuid` with `{{ primary_key }}` in the stub. Update the test assertion to use `$settings->primaryKey()`.

---

### 2. `getIndentSpace()` ignores `--indentation` option

**File:** `src/Domain/Stubs/StubReplacer.php:75-79`

```php
protected function getIndentSpace(int $tabs): string
{
    // Use always default indentation.
    return Str::repeat('    ', $tabs);
}
```

This always returns 4-space indentation regardless of the `--indentation` option. The code *works* because `replaceIndentation()` (line 295) does a global pass at the end, swapping all 4-space sequences for the configured indentation. However, this two-step approach is fragile — if a stub template ever uses non-standard indentation within the areas produced by `getIndentSpace()`, the swap would produce inconsistent results.

**Fix:** Change `getIndentSpace()` to use `$this->settings->indentSpace` and remove the global `replaceIndentation()` pass.

---

## Dead Code

### 3. `tests/Mocks/MockFilesystem.php` (128 lines) — completely unused

**File:** `tests/Mocks/MockFilesystem.php`

Implements `Illuminate\Contracts\Filesystem\Filesystem` but is never imported, instantiated, or referenced anywhere in the test suite. It is mentioned in `CLAUDE.md` and `CONTRIBUTING.md` as available, but no tests actually use it.

**Fix:** Delete the file and update docs references.

### 4. `coverage.svg` and `clover.xml` tracked in git

```
coverage.svg  (900 bytes, tracked)
clover.xml    (64 KB, tracked)
```

These are CI-generated coverage artifacts. `coverage.svg` is committed to git. `clover.xml` exists on disk but may or may not be tracked.

**Fix:** Add to `.gitignore` and remove from tracking with `git rm`.

---

## Maintainability & Architecture

### 5. `CommandSettings` has 17 constructor parameters

**File:** `src/Domain/Command/Models/CommandSettings.php:16-37`

The constructor accepts 17 parameters with mixed nullability and defaults:

```php
public function __construct(
    public readonly string $model,
    public readonly string $domain,
    public readonly string $namespace,
    public ?string $migration,
    public ?bool $createAggregate,
    public ?bool $createReactor,
    public readonly int $indentation,
    public array $notifications,
    public string $rootFolder,
    public ?bool $useUuid = null,
    public string $nameAsPrefix = '',
    public string $namespacePath = '',
    public string $domainPath = '',
    public string $testDomainPath = '',
    public bool $useCarbon = false,
    public bool $createUnitTest = false,
    public bool $createFailedEvents = false,
    array $modelProperties = [],
    array $ignoredProperties = [],
    public ?string $excludeMigration = null,
);
```

Several properties are set by the command's `bootstrap()` method after construction (`nameAsPrefix`, `domainPath`, `namespacePath`, `testDomainPath`). The large constructor makes instantiation error-prone — test code must pass many arguments even when they're irrelevant.

**Fix:** Introduce a builder pattern with fluent methods, or use stages:
- `CommandSettings::forModel(...)` — required fields
- `$settings->withAggregate(bool)` — optional features
- `$settings->finalise()` — computes derived fields

### 6. `AssertsDomainGenerated` (555 lines) — overloaded trait

**File:** `tests/Concerns/AssertsDomainGenerated.php`

This single trait mixes four distinct responsibilities:
1. **File listing** — `getExpectedFiles()` (lines 23-115): defines which files should/shouldn't exist per feature flags
2. **Stub-content expectation** — `getProjectorFailedEventMatches()`, `getProjectorNotificationMatches()`, `getReactorMatches()` (lines 117-166): expected string patterns per feature
3. **Content assertion** — `assertActions()`, `assertAggregate()`, `assertDataTransferObject()`, `assertEvents()`, `assertNotifications()`, `assertProjection()`, `assertProjector()`, `assertReactor()`, `assertTest()` (lines 168-417): per-file-type content validation
4. **Orchestration** — `assertDomainGenerated()` (lines 435-554): ties it all together

The `getExpectedFiles()` method duplicates the context-filtering logic already in `stubs/stub-mapping.json` and `Stubs::getStubResolvers()`, creating a maintenance burden — every new stub must be added in two places.

**Fix:** Extract into separate classes:
- `DomainFileExpectation` — expected/unexpected files per feature set
- `DomainContentAssertion` — per-type content assertions (could use strategy pattern with the stub name as key)
- Keep the orchestration method that ties them together

### 7. `HasBlueprintColumnType` used as trait in 7+ classes

**File:** `src/Domain/Blueprint/Concerns/HasBlueprintColumnType.php`

Used in:
- `StubReplacer`
- `MakeEventSourcingDomainCommand`
- `AssertsDomainGenerated`
- `MigrationCreatePropertyType`
- `CreatesMockMigration` (test)
- `ModifyMigration` (test)
- `HasBlueprintFake` (which itself is a trait)

Methods like `columnTypeToBuiltInType()` are pure stateless functions. Using a trait implies shared state or behavior, but these are utilities. Every class gets the full surface area even if it only uses one method.

**Fix:** Convert to a service class `ColumnTypeMapper` with static methods (or injectable service), and remove the trait usage.

### 8. `MakeEventSourcingDomainCommand` extends `GeneratorCommand` but overrides everything

**File:** `src/Console/Commands/MakeEventSourcingDomainCommand.php`

The class extends `Illuminate\Console\GeneratorCommand` but:
- `getStub()` returns `''` (unused, marked `@codeCoverageIgnore`)
- All stub resolution, replacement, and file creation is custom
- Only `rootNamespace()` from the parent is used

This creates unnecessary coupling to Laravel's generator infrastructure.

**Fix:** Extend `Illuminate\Console\Command` directly.

---

## Test Coverage Gaps

### 9. No direct unit tests for core classes

The entire test suite is integration-testing through the Artisan command. The following classes have **no direct unit tests**:

| Class | Risk | Lines |
|-------|------|-------|
| `MigrationParser` | Critical — migration parsing is the most complex logic | 66 |
| `BlueprintClassNodeVisitor` | Critical — AST traversal, property extraction | ~150 |
| `MigrationParser` | High — entry point for migration parsing | 66 |
| `StubReplacer` | High — all template replacement logic | 436 |
| `Stubs` | Medium — stub resolution and filtering | 98 |
| `StubResolver` | Medium — path resolution | ~60 |
| `CommandSettings` | Medium — config validation and inference | 58 |
| `Migration` | Medium — migration file loading | ~100 |
| `HasBlueprintColumnType` | Low — type mapping (currently implicitly tested) | 47 |
| `HasBlueprintFake` | Low — fake data generation | 30 |
| `CanCreateDirectories` | Low — directory creation (trivial) | ~30 |

The `MakeEventSourcingDomainCommandBasicTest` alone runs the full command for 5+ scenarios, making it an integration test that's slow to run and hard to debug when it fails.

**Fix:** Add direct unit tests for `StubReplacer`, `MigrationParser`, `BlueprintClassNodeVisitor`, `CommandSettings`, and `Migration`. These can test with mock/memory filesystem and hard-coded stub content.

### 10. `phpunit.xml` has `stopOnFailure="true"`

**File:** `phpunit.xml:7`

```xml
stopOnFailure="true"
```

This stops the test suite at the first failure. In CI, this means you only see the first regression — subsequent failures are hidden until the first is fixed.

**Fix:** Remove `stopOnFailure` (defaults to `false`). This is a personal-preference local config that shouldn't be committed, or add a comment explaining why it's intentional.

---

## Configuration / UX

### 11. `--notifications=no` default could be more robust

**File:** `src/Console/Commands/MakeEventSourcingDomainCommand.php:51`

```php
{--notifications=no : Indicate if notifications must be created...}
```

The default `"no"` is filtered out in `getNotifications()` because `"no"` is not in `AcceptedNotificationInterface::ACCEPTED`. However, passing `--notifications=` (empty string) or `--notifications=""` results in `[""]` which is a truthy array after `explode(',', "")`, so the code enters the notifications branch with an empty channels list.

**Fix:** Default to empty string and add an explicit `empty()` check in `getNotifications()`.

---

## Summary

| # | Priority | Area | Effort | Impact |
|---|----------|------|--------|--------|
| 1 | **Bug** | Slack notification hard-coded `uuid` | 5 min | High — generated code has incorrect display |
| 2 | **Bug** | `getIndentSpace()` ignores `--indentation` | 5 min | Medium — works by accident, fragile |
| 3 | **Clean** | Delete unused `MockFilesystem.php` | 2 min | Low — removes dead code / confusion |
| 4 | **Clean** | Gitignore `coverage.svg`, `clover.xml` | 2 min | Low — repo hygiene |
| 5 | **Refactor** | `CommandSettings` builder pattern | Half day | Medium — reduces instantiation errors |
| 6 | **Refactor** | Split `AssertsDomainGenerated` | Half day | Medium — isolates concerns, easier to extend |
| 7 | **Refactor** | `HasBlueprintColumnType` → service | Half day | Low — better composition |
| 8 | **Refactor** | Extend `Command` instead of `GeneratorCommand` | 30 min | Low — removes dead coupling |
| 9 | **Test** | Add unit tests for core classes | 1-2 days | High — catches regressions faster |
| 10 | **Fix** | Remove `stopOnFailure` from phpunit.xml | 2 min | Low — better CI output |
| 11 | **Fix** | `--notifications` empty string edge case | 10 min | Low — edge-case robustness |

