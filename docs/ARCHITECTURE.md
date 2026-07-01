# Architecture

> Reference detail extracted from `CLAUDE.md`. Load this when working on the internals; `CLAUDE.md` keeps only the per-session essentials.

## Core Components

**Console Command** (`src/Console/Commands/MakeEventSourcingDomainCommand.php`)
- Entry point for the `make:event-sourcing-domain` Artisan command
- Orchestrates the generation process using CommandSettings, Migration parsing, and stub replacement
- Extends Laravel's GeneratorCommand

**Migration Parser** (`src/Domain/PhpParser/MigrationParser.php`)
- Uses nikic/php-parser to parse existing Laravel migration files
- Extracts Blueprint column definitions from migration `up()` methods (excludes `down()` methods)
- Traverses AST using `BlueprintClassNodeVisitor` to identify model properties and their types
- Returns structured `MigrationCreateProperty` objects with field names, types, and modifiers

**Stub System** (`src/Domain/Stubs/`)
- `StubReplacer.php`: Handles template variable replacement using the Onion pipeline pattern
- `Stubs.php`: Manages stub file loading and output path resolution
- `stub-mapping.json`: Defines which stubs to generate based on context (aggregate, reactor, notifications, etc.)
- Template variables use both `DummyName` and `{{ kebab-case }}` / `{{kebab-case}}` formats

**Command Settings** (`src/Domain/Command/Models/CommandSettings.php`)
- Central configuration object holding all command options
- Properties include: model name, domain name, namespace, primary key type, aggregate/reactor flags, notification types
- Shared across all generation phases

**Blueprint Handling** (`src/Domain/Blueprint/`)
- `HasBlueprintColumnType`: Maps Blueprint column types (string, integer, etc.) to PHP types
- `HasBlueprintFake`: Generates Faker expressions for testing based on column types
- Supports standard Laravel Blueprint types; unsupported types are documented in docs/migrations.md

## Directory Structure

Generated domains follow this structure:
```
app/Domain/{DomainName}/{ModelName}/
├── Actions/               # Create/Update/Delete actions (with or without aggregates)
├── Aggregates/            # Aggregate root (if --aggregate=1 and primary key is uuid)
├── DataTransferObjects/   # DTOs for model data
├── Events/                # Domain events (Created, Updated, Deleted, *Failed)
├── Notifications/         # Event notifications (database, mail, Slack, Teams)
│   └── Concerns/          # Notification traits (HasDataAsArray, etc.)
├── Projections/           # Read model
├── Projectors/            # Event handlers for projections
└── Reactors/              # Side-effect handlers (if --reactor=1)

tests/Domain/{DomainName}/{ModelName}/
└── {ModelName}Test.php    # PHPUnit tests (if --unit-test)
```

Namespace and root folder are customizable via `--namespace` and `--root` options.

## Key Generation Flow

1. Parse command options into `CommandSettings`
2. If `--migration` specified, parse existing migration files to extract model properties
3. Load stub templates based on context (aggregate, reactor, failed-events, notifications)
4. Replace template variables using property information and settings
5. Generate files in target directories

## Migration Parsing Details

- Supports both exact migration names and patterns (e.g., `--migration=animal` matches `*animal*.php`)
- Can exclude migrations using `--migration-exclude` with exact names or regex patterns
- Only parses `up()` method content (ignores `down()` method to avoid confusion)
- Detects primary key type (uuid vs id) from migration
- Handles `Schema::create()` and `Schema::table()` patterns

## Testing

- Tests use Orchestra Testbench for Laravel package testing
- PHPUnit 11.x / 12.x with PHP 8.3/8.4/8.5
- Tests are organized by feature area under `tests/Unit/`
- The coverage badge is generated in CI (`.github/workflows/coverage.yml`, using pcov) and published to the orphan `coverage-data` branch; the README references it by raw URL. `coverage.svg` is not tracked on `main`. Run `composer test-coverage` locally for the HTML report in `reports/`.

## Dependencies

- PHP 8.3, 8.4 or 8.5
- Laravel 11.x (deprecated) / 12.x / 13.x (Testbench `^9 || ^10 || ^11`)
- Spatie Laravel Event Sourcing 7.x
- nikic/php-parser for migration parsing
- `Domain\Support\Pipeline` (in-package) for the left-to-right pipeline used in stub replacement and column-type mapping
