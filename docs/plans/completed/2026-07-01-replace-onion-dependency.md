# Replace the abandoned `aldemeery/onion` dependency

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** ✅ Completed 2026-07-01 (PR #27) — `aldemeery/onion` removed; replaced by `Domain\Support\Pipeline`.

_Maintenance / dependency task — no Management Summary (per the plan guidelines)._

## Changelog

- **2026-07-01** — Initial draft.
- **2026-07-01** — Design review: locked in `…\Domain\Support\Pipeline` with an **array constructor** and a single **`process()`** method (dropping the onion/`peel` metaphor); **no free helper**; `queue()` retyped to `array`. `add()`/`pipe()` deemed unnecessary.
- **2026-07-01** — ✅ Implemented via TDD (PR #27). Added `Pipeline` + `PipelineTest`, migrated the 4 call sites, removed `aldemeery/onion` from composer.json + lock. 83 tests / PHPStan / Pint green. Moved to `completed/`.

## Purpose

Remove the runtime dependency on [`aldemeery/onion`](https://github.com/aldemeery/onion) (a small pipeline/"onion" helper) which appears unmaintained, and replace it with a tiny in-package equivalent. No behaviour change.

## Background & findings

`aldemeery/onion` (`v1.0.2`) provides a pipeline abstraction: `(new Onion([$layer1, $layer2, …]))->peel($value)` runs `$value` through each layer. It is a **`require` (runtime) dependency**, so its health matters for every install.

### Where it's used (complete inventory)

| File | Usage |
|------|-------|
| `src/Domain/PhpParser/Models/MigrationCreatePropertyType.php` | `new Onion([...])->peel($this->type)` in `toBuiltInType()`, `toNormalisedBuiltInType()`, `toProjection()` (3 sites) |
| `src/Domain/Stubs/StubReplacer.php` | `onion([...])->peel($stub)` in `run()`; `queue(array\|Closure\|Invokable $layers)` builds the layer list; imports `onion()` helper + `Invokable` interface |
| `src/Console/Commands/MakeEventSourcingDomainCommand.php` | one `->queue([fn ($stub) => …])` call feeding a closure into the stub pipeline |
| `composer.json` | `"aldemeery/onion": "^1.0"` under `require` |

### What the library actually does here

Reading `vendor/aldemeery/onion/src/Onion.php`, `peel()` reduces the layers so that `peel(x)` = `lastLayer(… secondLayer(firstLayer(x)) …)` — i.e. **the value flows left-to-right through the layers**, each a plain `fn ($x) => transform($x)`. Confirmed by tracing its `array_reduce`/`stack()`.

**Crucially, this project uses only that simple pipe.** It does **not** use any of Onion's other features:
- No `$next`/middleware-style layers (every layer is a pure unary transform).
- No `addIf` / `addUnless` / `setExceptionHandler` / `withoutExceptionHandling`.
- No `LayerException` handling (verified: zero references in `src/`/`tests/`).
- No custom `Invokable` objects — the `Invokable` interface appears only as a **type hint** on `queue()`; every real argument is a `Closure`.

That means the replacement is a **~15-line reduction**, and the existing 79-test suite already exercises every call site (stub generation + migration-type mapping), so behaviour parity is directly verifiable.

## Options considered

### Option A — tiny in-package `Pipeline` class (recommended, design finalised)
Add `src/Domain/Support/Pipeline.php` — minimal, array constructor + a single `process()` method (design decisions below):

```php
namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Support;

final class Pipeline
{
    /** @param list<callable> $layers */
    public function __construct(private array $layers = []) {}

    public function process(mixed $passable): mixed
    {
        return array_reduce(
            $this->layers,
            fn (mixed $carry, callable $layer): mixed => $layer($carry),
            $passable,
        );
    }
}
```

**Finalised design decisions (review 2026-07-01):**
1. **Method name `process()`** — the "onion/peel" metaphor is dropped for a clearer name.
2. **Array constructor** `new Pipeline([$a, $b])` — mirrors the existing `new Onion([...])` sites and `StubReplacer`'s array-based `queue()` accumulation (smallest, clearest diff).
3. **No free helper** — drop the `onion()` function; call sites use `new Pipeline([...])` directly. Nothing added to the global namespace.
4. **Namespace `…\Domain\Support`** — a shared domain utility, used by both `PhpParser` and `Stubs`.

No `add()`/`pipe()` method is needed: `StubReplacer` keeps its own `$queue` array and passes the assembled list to the constructor.

- **Pros:** zero dependencies; full control; identical semantics; call sites barely change; trivially unit-testable (~10 lines).
- **Cons:** we own ~10 lines (a feature, not a cost, for something this small).

### Option B — Laravel's `Illuminate\Pipeline\Pipeline`
- **Cons:** different signature — layers must be middleware `($passable, $next)` and call `$next(...)`; needs a container instance; pulls in `illuminate/pipeline`. More churn and heavier for a pure value-pipe. Rejected.

### Option C — inline `Illuminate\Support\Collection` reduce
Replace each `(new Onion([...]))->peel($x)` with `collect([...])->reduce(fn ($c, $f) => $f($c), $x)`.
- **Cons:** scatters the pattern across call sites; the `StubReplacer::queue()` accumulation still needs a home. A named `Pipeline` reads better. Rejected in favour of A.

## Recommendation

**Option A.** It removes the abandoned runtime dependency with the smallest, clearest change and no new packages, and keeps the call sites almost identical (`Onion` → `Pipeline`, `onion([...])` → `new Pipeline(...)`).

## Steps

1. **Add** `src/Domain/Support/Pipeline.php` exactly as above (final class, array constructor, `process()`), matching the project's code style (typed, PHP 8.3+).
2. **Migrate `MigrationCreatePropertyType.php`** — replace `use Aldemeery\Onion\Onion;` with `use …\Domain\Support\Pipeline;`; change the 3 `(new Onion([...]))->peel($x)` to `(new Pipeline([...]))->process($x)`.
3. **Migrate `StubReplacer.php`** — drop `use function Aldemeery\Onion\onion;` and `use Aldemeery\Onion\Interfaces\Invokable;`; in `run()`, change `onion([...])->peel($stub)` to `(new Pipeline([...]))->process($stub)`; retype `queue(array|Closure|Invokable $layers): self` → `queue(array $layers): self`. Keep the `$queue` accumulation as-is.
4. **`MakeEventSourcingDomainCommand.php`** — no change needed (it only passes a closure array to `queue()`); verify it still type-checks.
5. **Remove** `"aldemeery/onion"` from `composer.json` `require`; `composer update aldemeery/onion` (or a full update) to drop it from the lock.
6. **Add a focused unit test** `tests/Unit/Domain/Support/PipelineTest.php` (left-to-right order, empty-pipeline identity, single/multi layer, non-closure `callable`) — aligns with the existing "direct unit tests for core classes" effort.
7. **Grep** to confirm no remaining `Onion`/`onion`/`aldemeery`/`Invokable` references in `src/`, `tests/`, `composer.json`.

## Testing

- `composer test` — the full 79-test suite covers stub generation and migration-type mapping (every call site); it must stay green, proving behaviour parity.
- `composer static` (PHPStan level 5) + `composer check` (Pint) green.
- New `PipelineTest` passing.

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Subtle ordering difference vs Onion | Traced Onion's `peel` = left-to-right; `array_reduce` reproduces it exactly; the suite verifies real output |
| Hidden reliance on Onion exception wrapping | Verified none (`LayerException` unused) |
| Type-hint fallout from dropping `Invokable` | Only used on `queue()`; retype to `array`/`callable`; PHPStan catches any miss |

## References

- Onion internals: `vendor/aldemeery/onion/src/Onion.php` (`peel`, `stack`).
- Call sites: `src/Domain/PhpParser/Models/MigrationCreatePropertyType.php`, `src/Domain/Stubs/StubReplacer.php`, `src/Console/Commands/MakeEventSourcingDomainCommand.php:136`.
- Related: `docs/plans/2026-06-16-direct-unit-tests-for-core-classes.md` (unit-test convention for core classes).

## Feedback

_(Reviewer comments below.)_
