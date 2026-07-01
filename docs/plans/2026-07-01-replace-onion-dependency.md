# Replace the abandoned `aldemeery/onion` dependency

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** Draft — awaiting approval

_Maintenance / dependency task — no Management Summary (per the plan guidelines)._

## Changelog

- **2026-07-01** — Initial draft.

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

### Option A — tiny in-package `Pipeline` class (recommended)
Add `src/Domain/Support/Pipeline.php` that mirrors the minimal API actually used:

```php
namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Support;

final class Pipeline
{
    /** @var list<callable> */
    private array $layers;

    public function __construct(callable ...$layers)
    {
        $this->layers = $layers;
    }

    public function pipe(callable ...$layers): self
    {
        $this->layers = [...$this->layers, ...$layers];

        return $this;
    }

    public function peel(mixed $passable): mixed
    {
        return array_reduce(
            $this->layers,
            fn (mixed $carry, callable $layer): mixed => $layer($carry),
            $passable,
        );
    }
}
```

- **Pros:** zero dependencies; full control; identical semantics; call sites barely change; trivially unit-testable.
- **Cons:** we own ~15 lines (a feature, not a cost, for something this small).

### Option B — Laravel's `Illuminate\Pipeline\Pipeline`
- **Cons:** different signature — layers must be middleware `($passable, $next)` and call `$next(...)`; needs a container instance; pulls in `illuminate/pipeline`. More churn and heavier for a pure value-pipe. Rejected.

### Option C — inline `Illuminate\Support\Collection` reduce
Replace each `(new Onion([...]))->peel($x)` with `collect([...])->reduce(fn ($c, $f) => $f($c), $x)`.
- **Cons:** scatters the pattern across call sites; the `StubReplacer::queue()` accumulation still needs a home. A named `Pipeline` reads better. Rejected in favour of A.

## Recommendation

**Option A.** It removes the abandoned runtime dependency with the smallest, clearest change and no new packages, and keeps the call sites almost identical (`Onion` → `Pipeline`, `onion([...])` → `new Pipeline(...)`).

## Steps

1. **Add** `src/Domain/Support/Pipeline.php` (as above), matching the project's code style (final class, typed, PHP 8.3+).
2. **Migrate `MigrationCreatePropertyType.php`** — replace `use Aldemeery\Onion\Onion;` with the new `Pipeline`; change the 3 `new Onion([...])->peel(...)` to `(new Pipeline(...))->peel(...)` (spread the closures as varargs, or keep an array-accepting constructor — decide during impl for the cleanest diff).
3. **Migrate `StubReplacer.php`** — drop `use function Aldemeery\Onion\onion;` and `use Aldemeery\Onion\Interfaces\Invokable;`; change `run()` to use `Pipeline`; retype `queue(array|Closure|Invokable $layers)` → `queue(array $layers)` (or `array|callable`). Keep the `queue`/`$queue` accumulation behaviour.
4. **`MakeEventSourcingDomainCommand.php`** — no change needed (it only passes a closure array to `queue()`); verify it still type-checks.
5. **Remove** `"aldemeery/onion"` from `composer.json` `require`; `composer update` to drop it from the lock.
6. **Add a focused unit test** `tests/Unit/Domain/Support/PipelineTest.php` (left-to-right order, empty-pipeline identity, single/multi layer, non-closure `callable`) — aligns with the existing "direct unit tests for core classes" effort.
7. **Grep** to confirm no remaining `Onion`/`onion`/`aldemeery` references in `src/`, `tests/`, `composer.json`.

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
