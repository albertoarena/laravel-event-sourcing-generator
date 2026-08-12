# Direct Unit Tests for Core Classes

**Date:** 2026-06-16
**Author:** Alberto Arena (with Claude)
**Status:** Proposed — revised 2026-08-12 against current `src/`

## Changelog

- 2026-06-16: Initial plan, scoped from item #9 of `docs/plans/2026-06-09-codebase-review-improvements.md`
- 2026-08-12: Revised against the codebase. The original was written before the Pipeline refactor (`docs/plans/completed/2026-07-01-replace-onion-dependency.md`, 2026-07-01) and described APIs that have since changed. Corrections: `MigrationParser` takes migration **content**, not a path; the seven `StubReplacer` methods listed for Phase 2 are mostly `protected`; `Migration` and `Stubs` use the `File` facade and cannot run container-free. Phases are re-ordered by **testability** rather than by guessed risk, the `tests/Fixtures/migrations/` step is dropped, and the `getIndentSpace()` lock-in test is reconsidered against postponed review item #2.

## Purpose

The current test suite is **entirely integration-driven** — every behaviour is exercised through `artisan make:event-sourcing-domain`. This has three consequences:

1. **Slow feedback** — each test boots Orchestra Testbench and runs the full generation pipeline; the suite takes seconds even for trivial logic checks.
2. **Hard to localise failures** — when a test fails, the stack trace points to the Artisan invocation, not the class that misbehaved. A bug in `MigrationParser` surfaces as "expected file content X did not match" three layers downstream.
3. **No safety net for edge cases that don't round-trip** — methods on `MigrationParser`, `BlueprintClassNodeVisitor`, `StubReplacer`, and `Migration` have branches (e.g. unsupported column types, malformed migrations, regex/glob matching, ignored properties) that are not exhaustively reachable through the public command surface.

This plan adds **targeted, fast PHPUnit tests at the class level** for the high-risk core classes. Integration tests stay; unit tests sit alongside them.

## Testability survey (2026-08-12)

Phasing follows this, because it determines whether a test is fast and container-free or has to boot Testbench:

| Class | Container needed? | Why |
|---|---|---|
| `MigrationParser` | **No** | Takes a content string; only `nikic/php-parser` |
| `BlueprintClassNodeVisitor` | **No** | Pure AST visitor |
| `CommandSettings` | **No** | `Str::repeat` is a plain static; no facade root required |
| `StubReplacer` (+ `HasBlueprintColumnType`, `HasBlueprintFake`) | **No** | Verified: no `Facades`, `app()`, `resolve()`, `config()`, or `*_path()` usage in the class or either trait |
| `Migration` | **Yes** | `Illuminate\Support\Facades\File` (3 call sites); constructor calls `parse()` at `Migration.php:36` |
| `Stubs` | **Yes** | `File` facade + injected `Illuminate\Contracts\Foundation\Application` |
| `StubResolver` | **Yes** | `resolvePath(Application $laravel)` |

### Resolving the `StubReplacer` visibility problem

Ten of the replacement methods are `protected` — `getIndentSpace()` (`StubReplacer.php:73`), `replaceDomain()` (`:79`), `replaceConstructorProperties()` (`:88`), `replaceProjectionFillableProperties()` (`:131`), `replaceIndentation()` (`:293`), and others. The public surface is `replace()` (`:379`), `afterReplacements()` (`:395`), `replaceWithClosureRegexp()` (`:403`), `replaceWithClosure()` (`:410`), `queue()` (`:419`), `run()` (`:426`).

**Decision: test through the public surface. Do not use reflection and do not widen visibility.**

`replace()` applies the protected replacers in a fixed order on a string passed by reference. Feeding it a **minimal stub containing only the token under test** isolates each replacer nearly as well as calling it directly, while keeping `src/` untouched (the plan's own out-of-scope rule, and CLAUDE.md guards `src/` as core). This also satisfies the original risk mitigation — assert observable outputs, never internal call sequences.

Rejected alternatives: reflection (brittle, and PHPStan/Larastan will flag it), and promoting methods to `public` (an API change to a core class, made solely to serve tests).

## Scope (prioritised)

### Phase 1 — Pure AST parsing (highest value, no container)

#### 1. `BlueprintClassNodeVisitor` (`src/Domain/PhpParser/Traversers/`)

Cover:
- Detects properties from `Schema::create()` blueprint closures
- Detects properties from `Schema::table()` blueprint closures
- Ignores `down()` method content
- Captures column modifiers (`->nullable()`, `->unique()`, `->default(...)`, `->index()`)
- Identifies primary key type (`uuid` vs `id`)
- Handles unsupported column types (sets `isDropped`/`warning`)
- Skips `timestamps()` correctly
- Handles aliases like `bigIncrements`, `foreignId`, `foreignUuid`

#### 2. `MigrationParser` (`src/Domain/PhpParser/`)

Actual API — `__construct(?string $migrationContent)` (`:17`), `parse(): self` (`:50`), `getProperties(): array` (`:57`), `getIgnored(): array` (`:62`).

Cover:
- `parse()` populates properties, `getProperties()` returns them re-indexed
- Malformed PHP throws `ParserFailedException`
- Content with no `up()` method yields an empty property set
- Empty `up()` yields an empty property set
- `null` content behaviour (pin whatever it currently does)
- Composes correctly with `BlueprintClassNodeVisitor`

**Fixtures:** inline heredoc strings in the test, **not** files. `MigrationParser` consumes content, so a `tests/Fixtures/migrations/` folder would need a redundant read step. Step 1 of the original plan is dropped.

**Assertion rule (important):** assert only on `MigrationCreateProperty` fields (`name`, `type`, modifiers, `isDropped`). Never assert on `PhpParser\Node` objects. CI runs `prefer-lowest` (pinning `nikic/php-parser` at 5.1.0) alongside `prefer-stable` across 36 matrix jobs; node-shape assertions are the one thing that will diverge between them.

### Phase 2 — Settings and stub templating (no container)

#### 3. `CommandSettings` (`src/Domain/Command/Models/`)

Do this first — it is cheap, and Phase 2's helper depends on understanding it.

Cover:
- `primaryKey()` (`:43`) returns `uuid`/`id` based on `useUuid`
- `inferUseCarbon()` (`:48`) flips when any property is Carbon-typed, and **not** for the `timestamps` virtual property
- `indentSpace` is derived from `indentation`

#### 4. Test helper: `CommandSettings` factory

`CommandSettings::__construct` takes 20 parameters, 9 of them required (`CommandSettings.php:17-36`), and `StubReplacer` holds it **by reference** (`public CommandSettings &$settings`), so tests must bind a variable before constructing the replacer.

Add one helper (`tests/Concerns/` or `tests/Support/`) exposing sensible defaults plus named overrides. Every StubReplacer test goes through it, so a future `CommandSettings` signature change — review item #5 (builder pattern) is postponed, not cancelled — touches one file instead of every test.

#### 5. `StubReplacer` (`src/Domain/Stubs/`)

Via `replace()`, `afterReplacements()` and `run()`, each with a minimal inline stub:

- Domain/namespace/id injection (`{{ domain }}` and friends)
- Constructor properties: correct PHP signatures, ignored-property TODOs, and the empty-constructor placeholder path
- Projection fillable properties: primary key injected first
- Projection cast and PHPDoc properties
- Primary key replacement for `uuid` vs `id`
- `if` block handling
- Indentation: swapped only when the configured value differs from 4
- Unit-test stub replacement
- `afterReplacements()`: `use`-namespace ordering, empty-line collapsing
- `replaceWithClosure()` across all three token formats (`DummyName`, `{{ kebab-case }}`, `{{kebab-case}}`) and `replaceWithClosureRegexp()` for regex-with-arg patterns
- `queue()` + `run()`: queued layers run between `replace()` and `afterReplacements()` (the `Pipeline` contract at `:426`)

**On `getIndentSpace()`:** the original plan wanted a test to "lock in the contract before any refactor." Parent review item #2 records that this method ignores `--indentation` and is **Postponed** because a proper fix means tokenising indentation in every stub file. A plain lock-in test would assert defective behaviour and later read as a regression. Either skip it, or write it with a docblock naming item #2 and stating that the assertion must change when #2 is fixed. Do not leave it unmarked.

### Phase 3 — Container-bound classes (decide before starting)

These need Testbench, so they are **not** the fast unit tests this plan set out to add. They are worth writing, but as focused near-unit tests, and the cost should be acknowledged rather than discovered.

#### 6. `Migration` (`src/Domain/Migrations/`)

Cover: exact-name loading; pattern loading (`animal` → `*animal*.php`); `--migration-exclude` exact and regex; primary key detection; property aggregation across multiple matches; error when nothing matches.

Reuse `tests/Concerns/CreatesMockMigration.php` — it already builds create/update migrations and all eight integration suites depend on it. A parallel fixture folder would drift from it.

Note that the constructor parses eagerly (`Migration.php:36`), so error-path tests assert on construction, not on a later call. Glob ordering can differ between darwin (dev) and ubuntu (CI) — assert on sets, not on order.

#### 7. `Stubs` / `StubResolver` (`src/Domain/Stubs/`)

Cover: context filtering (`notifications` / `aggregate` / `reactor` / `failed_events` keys in `stub-mapping.json`); `getStubResolvers()` (`Stubs.php:41`) returns the expected set per `CommandSettings` flag combination.

#### 8. `HasBlueprintColumnType` + `HasBlueprintFake`

Exercised implicitly via Phase 2 (both traits are used by `StubReplacer`). Add direct tests only if a gap shows up.

## Out of scope

- Refactoring the classes under test. The point is to **pin current behaviour** so future refactors (parent review items #5, #6, #7) become safer. Anything that looks wrong is a finding for a separate fix, not a scope expansion. This explicitly includes changing method visibility on `StubReplacer`.
- Replacing existing integration tests. They stay as black-box regression coverage.
- Adding mutation testing / coverage targets — separate concern.

## Steps

1. Mirror `src/` structure under `tests/Unit/Domain/` (`PhpParser/Traversers/`, `Stubs/`, `Command/Models/`). Note `tests/Domain/` already exists and holds **helpers**, not tests — don't add tests there.
2. Phase 1: `BlueprintClassNodeVisitor`, then `MigrationParser`. Inline heredoc migration blobs. Run `composer test` after each class.
3. Phase 2: `CommandSettings`, then the settings factory helper, then `StubReplacer`. Run after each.
4. Run `composer all` (test + fix + check + static) and confirm no regressions.
5. Re-check coverage (`composer test-coverage`) — the badge will move; that is expected, not a failure.
6. Phase 3 only after an explicit decision to accept Testbench-bound tests.
7. Note in `docs/plans/completed/2026-06-09-codebase-review-improvements.md` that item #9 has been addressed, and move this plan to `docs/plans/completed/`.

No `phpunit.xml` change is needed: the single test suite already globs `./tests` for `*Test.php`, so new files are auto-discovered.

## References

- Parent review: `docs/plans/completed/2026-06-09-codebase-review-improvements.md` item #9 (and item #2 for the `getIndentSpace()` caveat, item #5 for `CommandSettings`)
- Refactor that invalidated the original Phase 2: `docs/plans/completed/2026-07-01-replace-onion-dependency.md`
- Existing unit-test conventions: `tests/Unit/Domain/PhpParser/Models/MigrationCreatePropertyTypeTest.php` and `tests/Unit/Domain/Support/PipelineTest.php` (small, focused, no Testbench)
- Existing integration-test conventions: `tests/Unit/Console/Commands/MakeEventSourcingDomainCommandBasicTest.php`
- Migration helper to reuse in Phase 3: `tests/Concerns/CreatesMockMigration.php`

## Effort estimate

AI-assisted:
- Phase 1: ~half day (down from ~1 day — dropping the fixture folder removes the bulk of the original estimate)
- Phase 2: ~1 day (settings factory is the fiddly part, not the assertions)
- Phase 3: ~half day, only if approved

Traditional: roughly 2–3× the above.

## Risks & Mitigations

- **Risk:** Testing `StubReplacer` through `replace()` weakens failure localisation — a break in any of ten replacers surfaces as one string mismatch. **Mitigation:** one minimal stub per replacer, containing only that replacer's token, so the failing test name still identifies the culprit.
- **Risk:** Unit tests freeze incidental behaviour that future refactors want to change. **Mitigation:** assert on observable outputs (parsed properties, generated strings), never on private state or call sequences. Where a pinned behaviour is a known defect, say so in the test docblock (see `getIndentSpace()`).
- **Risk:** AST assertions break under `prefer-lowest` vs `prefer-stable`. **Mitigation:** assert on `MigrationCreateProperty`, never on `PhpParser\Node`.
- **Risk:** The 20-parameter `CommandSettings` constructor couples every Phase 2 test to one signature. **Mitigation:** the factory helper in step 4 — one place to change.
- **Risk:** Phase 3 fixtures drift from `CreatesMockMigration`. **Mitigation:** reuse that trait; do not create a second source of migration truth.
- **Risk:** Scope creep into refactoring. **Mitigation:** if a test is awkward to write because the class is awkward to use, log the friction in the parent review and keep the test ugly. Refactor is a separate plan.
- **Risk (met once already):** this plan drifts from `src/` again before it is executed. **Mitigation:** re-run the testability survey above before starting; it is cheap and it is what caught the 2026-07-01 drift.

## Feedback

_To be filled by reviewer._
