# Direct Unit Tests for Core Classes

**Date:** 2026-06-16
**Author:** Alberto Arena (with Claude)
**Status:** Proposed

## Changelog

- 2026-06-16: Initial plan, scoped from item #9 of `docs/plans/2026-06-09-codebase-review-improvements.md`

## Purpose

The current test suite is **entirely integration-driven** — every behaviour is exercised through `artisan make:event-sourcing-domain`. This has three consequences:

1. **Slow feedback** — each test boots Orchestra Testbench and runs the full generation pipeline; the suite takes seconds even for trivial logic checks.
2. **Hard to localise failures** — when a test fails, the stack trace points to the Artisan invocation, not the class that misbehaved. A bug in `MigrationParser` surfaces as "expected file content X did not match" three layers downstream.
3. **No safety net for edge cases that don't round-trip** — methods on `MigrationParser`, `BlueprintClassNodeVisitor`, `StubReplacer`, and `Migration` have branches (e.g. unsupported column types, malformed migrations, regex/glob matching, ignored properties) that are not exhaustively reachable through the public command surface.

This plan adds **targeted, fast PHPUnit tests at the class level** for the high-risk core classes. Integration tests stay; unit tests sit alongside them.

## Scope (prioritised)

### Phase 1 — AST / migration parsing (highest value, highest risk)

These classes contain the most non-obvious logic and have the most ways to silently mis-parse.

#### 1. `BlueprintClassNodeVisitor` (`src/Domain/PhpParser/`)

Cover:
- Detects properties from `Schema::create()` blueprint closures
- Detects properties from `Schema::table()` blueprint closures
- Ignores `down()` method content
- Captures column modifiers (`->nullable()`, `->unique()`, `->default(...)`, `->index()`)
- Identifies primary key type (`uuid` vs `id`)
- Handles unsupported column types (sets `isDropped`/`warning`)
- Skips `timestamps()` correctly
- Handles aliases like `bigIncrements`, `foreignId`, `foreignUuid`

Fixtures: small inline AST trees built with `nikic/php-parser`, OR small migration string blobs parsed in-test. Prefer string blobs — they read like real migrations.

#### 2. `MigrationParser` (`src/Domain/PhpParser/`)

Cover:
- `parse(string $path)` returns a `MigrationCreateProperties` collection
- Throws/returns sensibly on missing file
- Throws/returns sensibly on file that has no `up()` method
- Returns empty collection when `up()` is empty
- Composes correctly with `BlueprintClassNodeVisitor`

Fixtures: a few `.php` migration files under `tests/Fixtures/migrations/` (create the folder).

#### 3. `Migration` (`src/Domain/Migrations/`)

Cover:
- Loads exact migration by name
- Loads by pattern (`animal` → `*animal*.php`)
- Respects `--migration-exclude` exact match
- Respects `--migration-exclude` regex
- Detects primary key type from the loaded migrations
- Aggregates properties across multiple matched migrations
- Errors when no migration matches

### Phase 2 — Stub templating

#### 4. `StubReplacer` (`src/Domain/Stubs/`)

Cover, in isolation (no real stub files — pass small inline strings to `replace()`):
- `replaceWithClosure()` replaces all three formats (`DummyName`, `{{ kebab-case }}`, `{{kebab-case}}`)
- `replaceWithClosureRegexp()` handles the regex-with-arg patterns
- `replaceDomain()` injects domain, namespace, id
- `replaceConstructorProperties()` produces correct PHP signatures and ignored-property TODOs
- `replaceProjectionFillableProperties()` injects primary key first
- `replaceIndentation()` swaps 4-space → configured indentation only when different
- `getIndentSpace()` (current behaviour — locks in the contract before any refactor — see item #2 in the parent review)
- Carbon detection inferred from properties

#### 5. `Stubs` (`src/Domain/Stubs/`)

Cover:
- Context filtering: `notifications` / `aggregate` / `reactor` / `failed_events` keys in `stub-mapping.json` resolve correctly
- `getStubResolvers()` returns expected set per `CommandSettings` flag combination

### Phase 3 — Lower-priority (only if Phase 1+2 reveal coverage gaps)

#### 6. `CommandSettings`

Cover:
- `primaryKey()` returns `uuid`/`id` based on `useUuid`
- `inferUseCarbon()` flips when any property is Carbon-typed (excluding the `timestamps` virtual property)

#### 7. `HasBlueprintColumnType` + `HasBlueprintFake`

Currently exercised implicitly via integration tests. Direct unit tests are mostly redundant; add only if Phase 1 surfaces a gap or if these are refactored into a service (parent review item #7).

## Out of scope

- Refactoring the classes under test. The point of this work is to **pin current behaviour** so future refactors (parent review items #5, #6, #7) become safer. Treat anything that looks wrong as a finding for a separate fix, not a scope expansion.
- Replacing existing integration tests. They stay as black-box regression coverage.
- Adding mutation testing / coverage targets — separate concern.

## Steps

1. Create `tests/Fixtures/migrations/` with a handful of representative migration files (uuid PK, id PK, mixed types, with exclusions, with unsupported types).
2. Mirror `src/` structure under `tests/Unit/Domain/` for the new unit tests (some already exist for `PhpParser/Models/`).
3. Phase 1: write tests for `BlueprintClassNodeVisitor`, `MigrationParser`, `Migration`. Run after each class.
4. Phase 2: write tests for `StubReplacer`, `Stubs`. Run after each class.
5. Run full suite (`composer test`) and confirm no regressions.
6. Phase 3 only if Phase 1/2 leave obvious gaps.
7. Note in `docs/plans/2026-06-09-codebase-review-improvements.md` that item #9 has been addressed and link to this plan's completed location.

## References

- Parent review: `docs/plans/2026-06-09-codebase-review-improvements.md` item #9
- Existing unit-test conventions: `tests/Unit/Domain/PhpParser/Models/MigrationCreatePropertyTypeTest.php` (good template — small, focused, no Testbench)
- Existing integration-test conventions: `tests/Unit/Console/Commands/MakeEventSourcingDomainCommandBasicTest.php`

## Effort estimate

AI-assisted:
- Phase 1: ~1 day (visitor + parser + migration; fixtures are the bulk of the work)
- Phase 2: ~half day
- Phase 3: ~couple of hours if needed

Traditional: roughly 2–3× the above.

## Risks & Mitigations

- **Risk:** Unit tests freeze incidental behaviour that future refactors want to change. **Mitigation:** focus assertions on observable outputs (parsed properties, generated strings), not internal call sequences. Don't assert on private state.
- **Risk:** Migration fixtures drift from real Laravel migration syntax. **Mitigation:** copy the templates from actual `database/migrations/` examples in the testbench skeleton.
- **Risk:** Scope creep into refactoring. **Mitigation:** if a test is awkward to write because the class is awkward to use, log the friction in the parent review plan and keep the test ugly. Refactor is a separate plan.

## Feedback

_To be filled by reviewer._
