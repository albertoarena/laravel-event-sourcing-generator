# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a Laravel package that generates event sourcing domain structures for [Spatie's Laravel Event Sourcing](https://github.com/spatie/laravel-event-sourcing). It provides an Artisan command (`make:event-sourcing-domain`) that scaffolds complete domain directories with events, projections, projectors, aggregates, reactors, actions, DTOs, notifications, and PHPUnit tests.

## Development Commands

### Running Tests
```bash
composer test                    # Run all PHPUnit tests
composer test-coverage          # Generate coverage report and badge
```

### Code Quality
```bash
composer fix                    # Auto-fix code style with Laravel Pint
composer check                  # Check code style without fixing
composer static                 # Run PHPStan/LaraStan static analysis
composer all                    # Run test, fix, check, and static in sequence
```

### Package Commands
```bash
composer clear                  # Clear testbench skeleton
composer prepare                # Discover packages
composer build                  # Build testbench workbench
```

## Architecture

### Core Components

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

### Directory Structure

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

### Key Generation Flow

1. Parse command options into `CommandSettings`
2. If `--migration` specified, parse existing migration files to extract model properties
3. Load stub templates based on context (aggregate, reactor, failed-events, notifications)
4. Replace template variables using property information and settings
5. Generate files in target directories

### Migration Parsing Details

- Supports both exact migration names and patterns (e.g., `--migration=animal` matches `*animal*.php`)
- Can exclude migrations using `--migration-exclude` with exact names or regex patterns
- Only parses `up()` method content (ignores `down()` method to avoid confusion)
- Detects primary key type (uuid vs id) from migration
- Handles `Schema::create()` and `Schema::table()` patterns

## Testing

- Tests use Orchestra Testbench for Laravel package testing
- PHPUnit 11.x with PHP 8.3/8.4
- Mock filesystem is available in `tests/Mocks/MockFilesystem.php`
- Tests are organized by feature area under `tests/Unit/`

## Dependencies

- PHP 8.3 or 8.4
- Laravel 10.x / 11.x
- Spatie Laravel Event Sourcing 7.x
- nikic/php-parser for migration parsing
- aldemeery/onion for pipeline pattern in stub replacement

## Important Notes

- Aggregates are only generated when primary key is `uuid`
- Failed events and notifications are opt-in via command flags
- Indentation is always 4 spaces (the `--indentation` option affects generated code formatting)
- Blueprint column type support is documented in docs/migrations.md; unsupported types should be noted there

## Git Commit Conventions

### Format
- Type: short subject line (max 50 chars).
- Detailed body paragraph explaining what and why (not how).

### Rules
- No Claude attribution - NEVER include "Generated with Claude Code" or "Co-Authored-By: Claude".
- Keep first line under 50 characters.
- Use heredoc for multi-line commit messages.

## Critical Boundaries - NEVER Cross These Lines

### DO NOT Read the following folders
- `.docs`

### DO NOT Modify These Core Files
- **`.gitignore`** - NEVER modify
- **Git-tracked files** - Assume files tracked by git are core files unless explicitly told otherwise

### Git Safety Rules - Follow These Strictly
1. **NEVER modify `.gitignore`**
2. **NEVER force commit** using `--no-verify`, `-f`, or similar flags
3. **ALWAYS check `git check-ignore <path>`** before committing files to verify they should be tracked
5. **Ask always before committing**