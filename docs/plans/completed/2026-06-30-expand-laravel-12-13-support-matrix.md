# Expand Laravel Support Matrix to 12 / 13 (and retire EOL 10 / 11)

**Date:** 2026-06-30
**Author:** Alberto Arena (with Claude)
**Status:** Completed (released as v1.1.0)

## Changelog

- 2026-06-30: Initial plan. Triggered by `composer audit` flagging 3 advisories in `laravel/framework` v11.51.0 (pulled transitively via `orchestra/testbench ^9`), none of which are patched within the EOL Laravel 11 branch.
- 2026-06-30: Approved with refinements — drop Laravel 10, keep Laravel 11 as deprecated floor, **add PHP 8.5** (moved in-scope). Implemented Phases 1–4: bumped larastan `^3`/phpstan `^2`, widened testbench to `^9 || ^10 || ^11`, added PHP 8.5 to `require`, fixed 10 PHPStan-2 findings, expanded CI matrix, updated docs. Verified locally on PHP 8.4: Laravel 11/12/13 each pass 79 tests + clean PHPStan + Pint; `composer audit` clean on Laravel 13. Remaining: Phase 5 (GitHub CI run incl. PHP 8.5, tag release).
- 2026-06-30: **Completed.** Phase 5 done — all 16 CI matrix jobs green (PHP 8.3/8.4/8.5 × Laravel 11/12/13, incl. PHP 8.5). Released as **v1.1.0** (commits `b081c9a`, `7c8ec77`; tag `v1.1.0`). Plan moved to `docs/plans/completed/`.

## Management Summary

**What is this?** The generator currently advertises and tests against Laravel 10 and 11 only. As of March 2026, both of those Laravel versions have reached end-of-life and no longer receive security patches. This plan moves the package's tested support window forward to **Laravel 12 and 13** (the two currently-supported releases) and clarifies what we still promise for Laravel 11.

**Why do we need it?**
- **Credibility:** Right now the README claims support for two EOL frameworks, and the CI matrix doesn't even exercise the older of the two (Laravel 10 is untested in practice). New users on Laravel 12/13 have no signal the tool works for them.
- **Security hygiene:** `composer audit` will keep flagging unpatchable advisories for as long as the test toolchain is pinned to Laravel 11. These are dev-only and don't affect consumers, but they create recurring noise in Dependabot and audits.
- **Low effort, low risk:** The package's runtime footprint on Laravel is tiny (`illuminate/contracts` + `illuminate/support`), so forward-compatibility is cheap to verify and unlikely to break.

**Where/how is it used?** This is a developer tool (installed under `require-dev`) that scaffolds event-sourcing domains. Its users are developers starting new domains — a population that skews heavily toward current Laravel versions.

**What does it enable?** A green test matrix on Laravel 12 and 13, an accurate README support table, and a clean `composer audit`.

**Investment:** Roughly half a day of AI-assisted work (1–2 days traditional). No infrastructure cost.

**Timeline:** Single minor release. Phases below are sequential but small.

**Risks & mitigations:**
| Risk | Mitigation |
| --- | --- |
| Static-analysis toolchain bump (larastan 2→3, phpstan 1→2) surfaces new findings | Run `composer static` early in Phase 2; triage findings before touching the matrix |
| Dropping Laravel 10/11 annoys laggard users | Laravel 11 floor is retained in the constraint for one release; deprecation is announced in CHANGELOG/README |

---

## Context & Findings

### Laravel support status (as of 2026-06-30)

Source: [Laravel support policy](https://laravel.com/docs/master/releases), [endoflife.date/laravel](https://endoflife.date/laravel).

| Version | PHP | Bug fixes until | Security fixes until | Status today |
| ------- | --------- | ------------------- | -------------------- | ------------ |
| 10 | 8.1–8.3 | Aug 6, 2024 | Feb 4, 2025 | **EOL** |
| 11 | 8.2–8.4 | Sep 3, 2025 | **Mar 12, 2026** | **EOL** (3+ months ago) |
| 12 | 8.2–8.5 | Aug 13, 2026 | Feb 24, 2027 | Supported |
| 13 | 8.3–8.5 | Q3 2027 | Q1 2028 | Supported (newest) |

**Headline:** the package's README (`README.md:57`) currently claims `Laravel 10.x / 11.x` — i.e. it advertises support for *only EOL versions*. The three `composer audit` advisories are unpatchable on the 11.x branch precisely because 11.x security support has ended.

### What the package actually requires

- Runtime (`require`): only `illuminate/contracts: *` and `illuminate/support: *` — no hard Laravel version coupling.
- `spatie/laravel-event-sourcing ^7.9` already supports `illuminate/* ^10 | ^11 | ^12 | ^13` — **no blocker** for Laravel 12/13.
- `php: ^8.3 | ^8.4` — compatible with Laravel 12 (8.2+) and Laravel 13 (8.3+). (Laravel 12/13 also allow PHP 8.5; adding it is optional and out of scope here.)

### What the CI matrix actually tests today

`.github/workflows/test.yml` matrixes `php: [8.3, 8.4]` × `dependency-version: [prefer-lowest, prefer-stable]`, but the Laravel version is pinned by `orchestra/testbench ^9`, which resolves to **Laravel 11 only**. So:
- Laravel 10 is **not actually tested** despite the README claim.
- Laravel 12 and 13 are **not tested at all**.

### Toolchain version mapping (verified against Packagist)

| Dependency | Current | Laravel 11 | Laravel 12 | Laravel 13 |
| --- | --- | --- | --- | --- |
| `orchestra/testbench` | `^9.0` | `^9` (PHP 8.2+) | `^10` (PHP 8.2+) | `^11` (PHP 8.3+) |
| `larastan/larastan` | `^2.9` | `^2.9` / `^3` | `^3` | `^3` |
| `phpstan/phpstan` | `^1.12` | `^1.12` / `^2` | `^2` | `^2` |
| `phpunit/phpunit` | `^11.4 \|\| ^12.0` | ✓ | ✓ | ✓ |

**Friction point:** `larastan ^2.9` requires `phpstan ^1.12` and supports `illuminate ^9 | ^10 | ^11` only — it does **not** support Laravel 12/13. To static-analyse against 12/13 we must bump to `larastan ^3` (which requires `phpstan ^2` and supports `illuminate ^11.44.2 | ^12.4.1 | ^13`). Note `larastan 3` drops Laravel 10 — acceptable since 10 is EOL.

### Adoption (Packagist, 2026-06-30)

- Total downloads: **10,049** · monthly: **447** · daily: **47**
- GitHub stars: **24** · Packagist dependents: **0** (expected — it is a `require-dev` tooling package, not a runtime dependency)

**Interpretation:** a small but steadily-used dev tool (~450 installs/month). Its audience is developers scaffolding *new* domains, who overwhelmingly start on current Laravel. The marginal value of continuing to guarantee EOL Laravel 10/11 is low; the value of advertising 12/13 is high.

---

## Decision: do we still need Laravel 11?

**Recommendation:**
- **Drop Laravel 10** outright — already EOL 16+ months, already untested in CI, README claim is inaccurate.
- **Keep Laravel 11 as the floor for one transitional release** — it only went EOL in March 2026, the cost of keeping it in the test matrix is near-zero (testbench `^9` coexists with `^10`/`^11`), and it smooths migration for laggard users. Announce it as deprecated.
- **Add Laravel 12 and 13** as the primary supported targets.
- **Re-evaluate dropping 11** at the next minor release once 12/13 are established.

Resulting advertised support: **Laravel 11 (deprecated) / 12 / 13**.

---

## Steps

### Phase 1 — Toolchain bump (static analysis)

1. In `composer.json` `require-dev`, bump:
   - `larastan/larastan` `^2.9` → `^3.0`
   - `phpstan/phpstan` `^1.12` → `^2.0`
2. Run `composer update larastan/larastan phpstan/phpstan --with-all-dependencies`.
3. Run `composer static`. Triage any new findings from the phpstan 2 / larastan 3 upgrade (rule changes are likely). Fix or baseline as appropriate — do **not** lower the configured level (currently 5 in `phpstan.neon`).
4. Run `composer test` and `composer check` to confirm nothing regressed on the current (Laravel 11) install.

### Phase 2 — Widen the dependency constraint

1. In `composer.json` `require-dev`, widen:
   - `orchestra/testbench` `^9.0` → `^9.0 || ^10.0 || ^11.0`
2. Confirm `phpunit/phpunit` (`^11.4 || ^12.0`) still satisfies all three testbench majors; widen to include `^12.0` minimum if testbench 11 requires it (verify during update).
3. Locally verify each target resolves and tests pass:
   ```bash
   composer update "orchestra/testbench:^10.0" --with-all-dependencies && composer test   # Laravel 12
   composer update "orchestra/testbench:^11.0" --with-all-dependencies && composer test   # Laravel 13
   composer update "orchestra/testbench:^9.0"  --with-all-dependencies && composer test   # Laravel 11 (floor)
   ```
4. Restore the lockfile to a sensible default (latest stable) after manual verification.

### Phase 3 — Expand the CI matrix

Rework `.github/workflows/test.yml` to test each Laravel version explicitly. Add a `laravel`/`testbench` dimension and constrain dependencies before install:

```yaml
strategy:
  fail-fast: false
  matrix:
    os: [ ubuntu-latest ]
    php: [ 8.3, 8.4 ]
    laravel: [ 11.*, 12.*, 13.* ]
    dependency-version: [ prefer-lowest, prefer-stable ]
    include:
      - laravel: 11.*
        testbench: 9.*
      - laravel: 12.*
        testbench: 10.*
      - laravel: 13.*
        testbench: 11.*
```

Add a step before `composer install`:

```yaml
- name: Constrain Laravel version
  run: >
    composer require
    "laravel/framework:${{ matrix.laravel }}"
    "orchestra/testbench:${{ matrix.testbench }}"
    --no-interaction --no-update
```

Notes:
- All PHP/Laravel combinations are valid (package floor is PHP 8.3; Laravel 13 needs 8.3+), so **no `exclude` blocks are required**. Re-verify if PHP 8.5 is added later.
- Keep `prefer-lowest` to catch usage of APIs newer than each Laravel version's floor.

### Phase 4 — Documentation

1. `README.md:57` — update the compatibility table to `Laravel 11 (deprecated) / 12 / 13`.
2. `CLAUDE.md` "Dependencies" section — change `Laravel 10.x / 11.x` to the new matrix.
3. `CONTRIBUTING.md:15` — update the transitive-Laravel note to reflect testbench `^9 || ^10 || ^11`.
4. `CHANGELOG.md` — add an entry: added Laravel 12/13 support, dropped Laravel 10, deprecated Laravel 11; bumped larastan/phpstan.
5. `.claude/done/2026-06-30-done.md` — log the work per project task-tracking rules.

### Phase 5 — Release & verify

1. Open a PR; confirm the full matrix (2 PHP × 3 Laravel × 2 dependency-version = 12 jobs) is green.
2. Re-run `composer audit` on a Laravel 12/13 lock — the three `laravel/framework` advisories should clear (fixed in 12.60.0+/12.61.1+/13.10.0+).
3. Tag a new minor release.

---

## Out of Scope

- Dropping Laravel 11 entirely (deferred to the following minor release).
- Any change to `require` runtime constraints — `illuminate/* : *` already permits all targets.

## References

- `composer audit` output (2026-06-30): 3 advisories in `laravel/framework` v11.51.0
- Laravel support policy: https://laravel.com/docs/master/releases
- Laravel EOL dates: https://endoflife.date/laravel
- `CONTRIBUTING.md:177` — existing "Security advisories (`composer audit`)" triage policy
- `.github/workflows/test.yml` — current CI matrix
- `README.md:57` — current compatibility table

## Feedback

_(reviewer comments here)_
