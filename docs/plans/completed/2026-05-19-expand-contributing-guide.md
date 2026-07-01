# Expand CONTRIBUTING.md

**Status:** ✅ Completed — the proposed sections (Prerequisites, Local setup, "Adding or modifying a stub", "Adding a Blueprint column type") are present in `CONTRIBUTING.md`. Note: the version numbers in the original steps (PHP 8.3/8.4, Laravel 10/11) are now superseded by the code-derived compatibility table on the docs site.

## Changelog

- 2026-05-19: Initial plan.
- 2026-07-01: Verified implemented in `CONTRIBUTING.md`; marked completed and moved to `completed/`.

## Purpose

The repository already has a `CONTRIBUTING.md`, but it predates several conventions now codified in `CLAUDE.md` and the current `composer.json` scripts, and it does not give contributors guidance on the two most common contribution shapes in this package (adding/changing a stub, and adding a Blueprint column type).

Scope of this plan: **targeted additions only** to the existing `CONTRIBUTING.md`. Keep the current section order and tone; add what is missing. No `.github` issue/PR templates, no `CODE_OF_CONDUCT.md`, no `SECURITY.md` — those are deferred.

## Steps

### 1. Add a "Prerequisites" section (above "How Can I Contribute?")

State the supported versions so contributors do not waste time on incompatible local setups:

- PHP 8.3 or 8.4
- Composer 2.x
- Laravel 10.x / 11.x (transitive, via Orchestra Testbench)
- Spatie Laravel Event Sourcing 7.x

### 2. Add a "Local setup" section (after Prerequisites)

Document the workbench bootstrap that `composer.json` exposes but `CONTRIBUTING.md` does not currently mention:

```bash
composer install
composer prepare   # package discovery
composer build     # build the Testbench workbench
```

### 3. Expand "Code Guidelines"

- Add `composer check` (Pint in `--test` mode) alongside `composer fix`.
- Add `composer all` as the single command CI mirrors (test + fix + check + static).
- Add a one-line note that `.github/workflows/` runs the same checks on PRs.

### 4. Add a "Commit message conventions" subsection inside "Submitting Code Changes"

Mirror the rules in `CLAUDE.md` and the style visible in `git log`:

- Type prefix matching recent history: `Feature:`, `Fix:`, `Docs:`, `Chore:`, `Refactor:`, `Test:`.
- Subject line ≤ 50 characters.
- Body paragraph explains *what* and *why*, not *how*.
- No Claude / AI co-author attribution (explicit, since CLAUDE.md forbids it).
- Use a heredoc for multi-line messages.

### 5. Add a "Update CHANGELOG" step to the PR workflow

Insert a step between "Test your changes" and "Commit" that asks contributors to add a bullet to `CHANGELOG.md` under an `## Unreleased` heading (creating it if it does not exist). Reference the existing file format.

### 6. Add a "PR checklist" subsection

A markdown task list contributors copy into their PR description:

```markdown
- [ ] `composer test` passes
- [ ] `composer check` passes (Pint)
- [ ] `composer static` passes (PHPStan/LaraStan)
- [ ] `CHANGELOG.md` updated under "Unreleased"
- [ ] Docs updated (`README.md`, `docs/`) if behaviour changed
- [ ] New/changed stubs covered by tests
```

### 7. Add a "Contributing patterns" section (after "Submitting Code Changes")

Two focused subsections covering the most common change shapes in this package. Keep each to a short bulleted recipe; do not duplicate `CLAUDE.md`.

**Adding or modifying a stub**

- Stubs live in `stubs/`.
- If introducing a new stub context (aggregate, reactor, notification, …), update `src/Domain/Stubs/stub-mapping.json`.
- Template variables use both `DummyName` and `{{ kebab-case }}` forms — match the surrounding stub.
- Add coverage under `tests/Unit/` exercising the new stub via `make:event-sourcing-domain`.

**Adding a Blueprint column type**

- Map the PHP type in `src/Domain/Blueprint/HasBlueprintColumnType.php`.
- Add a Faker expression in `src/Domain/Blueprint/HasBlueprintFake.php`.
- Document support (or known caveats) in `docs/migrations.md`.
- Add a parser test covering a migration that uses the new column type.

### 8. Add a short "Testing" subsection inside Code Guidelines

- Tests run under Orchestra Testbench (PHPUnit 11/12).
- Filesystem-touching tests should use `tests/Mocks/MockFilesystem.php`.
- Tests are organised by feature area under `tests/Unit/`.

### 9. Self-review pass (per CLAUDE.md)

After editing, re-read the updated `CONTRIBUTING.md` end-to-end:

- Does the section order still flow? (Prerequisites → Setup → How to contribute → Patterns → Code Guidelines → Etiquette → Help.)
- Is each new instruction actionable (a command, a file path, or a checklist item)?
- No duplication of `CLAUDE.md` content that contributors do not need.
- No new external links that require verification beyond the ones already in the file.

### 10. Out of scope (explicit non-goals)

- No `.github/PULL_REQUEST_TEMPLATE.md` or `ISSUE_TEMPLATE/`.
- No `CODE_OF_CONDUCT.md` or `SECURITY.md`.
- No restructure of the existing sections; only additions and small edits in place.
- No changes to `CLAUDE.md`, `composer.json`, `.github/workflows/`, or source files.

## References

- Existing file: [`CONTRIBUTING.md`](../../../CONTRIBUTING.md)
- Project conventions: [`CLAUDE.md`](../../../CLAUDE.md)
- Composer scripts: [`composer.json`](../../../composer.json)
- CI workflows: [`.github/workflows/`](../../../.github/workflows/)
- Blueprint column type docs: [`docs/migrations.md`](../../../docs/migrations.md)
- Stub mapping: [`src/Domain/Stubs/stub-mapping.json`](../../../src/Domain/Stubs/stub-mapping.json)

## Feedback

_(reviewer comments go here)_
