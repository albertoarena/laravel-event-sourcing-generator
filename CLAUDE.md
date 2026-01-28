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

## Claude AI Documentation

### Storage
- Store all Claude-related documentation in the `.claude` directory
- Plans: `.claude/plans/{developer}/` (e.g., `.claude/plans/alberto/`)
- Completed plans: `.claude/plans/{developer}/completed/`

### Plan File Format
- Format: `YYYY-MM-DD-<short-description>.md`
- Example: `2026-01-26-add-aggregate-validation.md`
- Include: date, purpose, and relevant context
- Keep changelog at top with dates and descriptions of changes
- Reference plans with links to specific files
- Add feedback in separate section at bottom

### Plan Structure
- **Title:** Clear, descriptive title
- **Changelog:** List of changes with dates
- **Purpose:** Brief summary of the plan's goal
- **Steps:** Detailed, step-by-step instructions for implementation
- **References:** Links to related documentation, code, or resources
- **Feedback:** Section for reviewers to provide comments or suggestions

### Writing Effective Plans
- Be specific about what needs to be done
- Provide context for why the task is necessary
- For complex tasks, break down steps into smaller sub-steps for clarity
- Use bullet points or numbered lists for easy readability
- Time estimates are optional in plan documents; if included, note AI-assisted vs traditional development ranges

### Plan Review (Self-Check)
**IMPORTANT:** After writing a plan, always re-read and review it before presenting to the user. Check for:
- **Clarity:** Are the steps clear and unambiguous?
- **Completeness:** Are any steps or edge cases missing?
- **Maintainability:** Will this approach be easy to maintain long-term?
- **Context:** Is there enough context for an AI assistant to implement this?
- **Improvements:** Are there areas that could be simplified or improved?

If issues are found during self-review, update the plan before asking for approval.

### Management Summary (Required for Major Features)
Significant features or systems that require stakeholder buy-in MUST include a **Management Summary** section immediately after the metadata (date, author, status). For minor tasks or bug fixes, this section can be omitted.

This summary is for non-technical stakeholders and should answer:

1. **What is this?** - One paragraph explaining the feature/system in plain language
2. **Why do we need it?** - Business benefits (bullet points)
3. **Where/How is it used?** - Who benefits and how they use it
4. **What does it enable?** - Key capabilities or example use cases
5. **Investment** - Effort estimate if needed for stakeholder planning (optional), infrastructure costs if applicable
6. **Timeline** - High-level phases with durations
7. **Risks & Mitigations** - Top 2-3 risks with how they're addressed

**Guidelines:**
- Use simple, non-technical language
- Keep it to one page maximum
- Use tables for comparisons and timelines
- Avoid jargon - explain technical terms if necessary
- Focus on business value, not implementation details

### Plan Completion
**IMPORTANT:** When a plan's implementation is fully completed:
1. Move the plan file from `.claude/plans/{developer}/` to `.claude/plans/{developer}/completed/`
2. Update the plan's changelog with the completion date
3. If the system/feature warrants it, create a priming document in `.claude/priming/`

A plan is considered complete when all implementation tasks are done and tested, not when it's just been written.

### Documentation Requirements
- Re-read this file (CLAUDE.md) after completing each task to keep instructions primed in context
- Check for `.claude/overrides.md` for any project-specific additions to these guidelines

## Task Tracking

### Daily Updates
- After completing each task, immediately update the daily done file:
- `.claude/done/YYYY-MM-DD-done.md`
- Include a summary of work done that day
- Use bullet points for each completed task with timestamps
- Provide detailed explanations for significant changes below the summary
- Update the summary section after each addition to reflect the day's work
- Example file structure:
  ```
  .claude/
      done/
          2024-06-01-done.md
          2024-06-02-done.md
  ```

### Guidelines
- Add a bullet to "Done Today" for each change with timestamp
- Create a detailed section below for each significant change
- Keep "Done Today" as a quick overview; details go in sections below
- After adding a new item to "Done Today", rewrite the Summary section to reflect the updated work
- A brief description (max 250 tokens total for the entire Summary section) covering any other work done that day. Written at the end of the day or when asked.

## Critical Boundaries - NEVER Cross These Lines

### DO NOT Read the following folders
- `/.docs` (internal documentation)
- `/.idea` (IDE configuration)
- `/.phpunit.cache` (test cache)
- `/reports` (coverage reports)
- `/vendor` (dependencies)

### DO NOT Modify These Core Files
- **`.gitignore`** - NEVER modify
- **Source and test files** (`src/`, `tests/`, `config/`, `composer.json`) - Treat as core files; only modify when explicitly asked
- Exceptions: `CLAUDE.md`, `.claude/`, `docs/`, and `README.md` may be updated as part of documentation tasks

### Git Safety Rules - Follow These Strictly
1. **NEVER modify `.gitignore`**
2. **NEVER force commit** using `--no-verify`, `-f`, or similar flags
3. **ALWAYS check `git check-ignore <path>`** before committing files to verify they should be tracked
4. **ALWAYS ask before committing** unless explicitly instructed to commit