# Laravel event sourcing generator

![build-test](coverage.svg)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/albertoarena/laravel-event-sourcing-generator.svg?style=flat-square)](https://packagist.org/packages/albertoarena/laravel-event-sourcing-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/albertoarena/laravel-event-sourcing-generator.svg?style=flat-square)](https://packagist.org/packages/albertoarena/laravel-event-sourcing-generator)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE.md)
![Code Size](https://img.shields.io/github/languages/code-size/albertoarena/laravel-event-sourcing-generator)

Laravel event sourcing generator scaffolds complete domain structures for [Spatie's Laravel Event Sourcing](https://github.com/spatie/laravel-event-sourcing), providing a single Artisan command to generate events, projections, projectors, aggregates, reactors, actions, DTOs, notifications, and PHPUnit tests.

**New to event sourcing?** Check out [Spatie's documentation](https://spatie.be/docs/laravel-event-sourcing) to understand projections, aggregates, and reactors.

## Table of Contents

- [Changelog](#changelog)
- [Contributing](#contributing)
- [Installation](#installation)
    - [Compatibility](#compatibility)
    - [Install](#install)
- [Quick Start](#quick-start)
- [What Gets Generated](#what-gets-generated)
- [Usage](#usage)
    - [Show help](#show-help)
    - [Basic usage](#basic-usage)
    - [Domain and namespace](#domain-and-namespace)
    - [Advanced usage](#advanced-usage)
        - [Set primary key](#set-primary-key)
        - [Generate PHPUnit tests](#generate-phpunit-tests)
        - [Generate aggregates](#generate-aggregates)
        - [Generate reactors](#generate-reactors)
        - [Generate failed events](#generate-failed-events)
        - [Generate notifications](#generate-notifications)
        - [Specify indentation](#specify-indentation)
        - [Specify the path of root folder](#specify-the-path-of-root-folder)
- [Limitations and future enhancements](#limitations-and-future-enhancements)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Feel free to fork, improve and create a pull request.

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Installation

### Compatibility

| What                                                                        | Version     |
|-----------------------------------------------------------------------------|-------------|
| PHP                                                                         | 8.3 / 8.4   |
| [Laravel](https://github.com/laravel/laravel)                               | 10.x / 11.x |
| [Spatie's event sourcing](https://github.com/spatie/laravel-event-sourcing) | 7.x         |

### Install

```shell
composer require albertoarena/laravel-event-sourcing-generator
```

## Quick Start

Generate a basic domain structure:

```shell
php artisan make:event-sourcing-domain Animal --domain=Animal
```

This creates a complete event-sourced domain with events, projections, projectors, and actions in `app/Domain/Animal/Animal/`.

For more advanced features, see the [Usage](#usage) section below.

## What Gets Generated

Running the command creates this structure in your `app/Domain/{DomainName}/{ModelName}/` directory:

**Always Generated:**
- `Actions/` - Create/Update/Delete action classes
- `DataTransferObjects/` - DTOs for model data
- `Events/` - Domain events (Created, Updated, Deleted)
- `Projections/` - Read model (Eloquent model)
- `Projectors/` - Event handlers that update projections

**Optional (with flags):**
- `Aggregates/` - Aggregate root (`--aggregate=1`, requires uuid primary key)
- `Reactors/` - Side-effect handlers (`--reactor=1`)
- `Notifications/` - Event notifications (`--notifications=database,mail,slack,teams`)
  - `Notifications/Concerns/` - Shared notification traits
- `tests/Domain/{DomainName}/{ModelName}/` - PHPUnit tests (`--unit-test`)

**With Failed Events (`--failed-events=1`):**
- Additional events: `{Model}CreationFailed`, `{Model}UpdateFailed`, `{Model}DeletionFailed`
- Corresponding notifications if `--notifications` is also specified

## Usage

```text
php artisan make:event-sourcing-domain <model> [options]
```

**Basic Options:**
- `-d|--domain=<domain>` - The name of the domain
- `--namespace=<namespace>` - The namespace or root folder (default: "Domain")
- `--root=<root>` - The name of the root folder (default: "app")

**Migration Options:**
- `-m|--migration=<migration>` - Existing migration for the model (with or without timestamp prefix, or table name)
- `--migration-exclude=<pattern>` - Migration pattern to exclude (supports regex)

**Feature Flags:**
- `-a|--aggregate=<0|1>` - Generate aggregate (requires uuid primary key)
- `-r|--reactor=<0|1>` - Generate reactor
- `-u|--unit-test` - Generate PHPUnit tests
- `--failed-events=<0|1>` - Generate failed event classes
- `--notifications=<types>` - Generate notifications (database,mail,slack,teams, or no)

**Model Configuration:**
- `-p|--primary-key=<uuid|id>` - Primary key type (default: uuid)
- `--indentation=<spaces>` - Indentation spaces for generated code (default: 4)

### Show help

```shell
php artisan help make:event-sourcing-domain
```

### Basic usage

[Documentation about basic usage](./docs/basic-usage.md)

#### Generate a model with same name of the domain

```shell
php artisan make:event-sourcing-domain Animal \
  --domain=Animal
```

#### Generate a model with different domain

[Read documentation with examples](./docs/domain-and-namespace.md#specify-the-name-of-the-domain)

```shell
php artisan make:event-sourcing-domain Tiger \
  --domain=Animal
```

#### Generate a model with different domain and namespace

[Read documentation with examples](./docs/domain-and-namespace.md#specify-the-namespace)

```shell
php artisan make:event-sourcing-domain Tiger \
  --domain=Animal \
  --namespace=CustomDomain 
```

#### Generate a model from existing migration

[Read documentation with examples](./docs/migrations.md)

```shell
php artisan make:event-sourcing-domain Animal \
  --migration=create_animal_table \
  --unit-test
```

#### Generate a model from existing migration using pattern and exclude specific one

[Read documentation with examples](./docs/migrations.md#generate-a-domain-using-update-migration-excluding-some-specific-migration)

```shell
php artisan make:event-sourcing-domain Animal \
  --migration=animal \
  --migration-exclude=drop_last_column_from_animals \
  --unit-test
```

#### Generate a model from existing migration using pattern and exclude using regex

[Read documentation with examples](./docs/migrations.md#generate-a-domain-using-update-migration-excluding-some-specific-migration)

```shell
php artisan make:event-sourcing-domain Animal \
  --migration=animal \
  --migration-exclude="/drop_.*_from_animals/" \
  --unit-test
```

#### Generate a model from existing migration with failed events and notifications

```shell
php artisan make:event-sourcing-domain Animal \
  --migration=create_animal_table \
  --failed-events=1 \
  --notifications=database,mail,slack
```

### Domain and namespace

[Read documentation about directory structure](./docs/domain-and-namespace.md#directory-structure)

#### Specify the name of the domain

[Read documentation with examples](./docs/domain-and-namespace.md#specify-the-name-of-the-domain)

```shell
php artisan make:event-sourcing-domain Animal --domain=Tiger
php artisan make:event-sourcing-domain Animal --domain=Lion
```

#### Specify the namespace

[Read documentation with examples](./docs/domain-and-namespace.md#specify-the-namespace)

```shell
php artisan make:event-sourcing-domain Tiger --namespace=MyDomain --domain=Animal
```

### Advanced usage

#### Set primary key

[Read documentation with examples](./docs/advanced-usage.md#specify-primary-key)

Default primary key is `uuid`. That will work with Aggregate class.

It is possible to use `id` as primary key:

```shell
php artisan make:event-sourcing-domain Animal --primary-key=id
```

When importing migrations, primary key will be automatically loaded from file.

#### Generate PHPUnit tests

[Read documentation with examples](./docs/unit-tests.md)

```shell
php artisan make:event-sourcing-domain Animal --unit-test
```

#### Generate aggregates

[Read documentation with examples](./docs/advanced-usage.md#generate-aggregates)

```shell
php artisan make:event-sourcing-domain Animal --aggregate=1
```

This is available only for models using `uuid` as primary key.

#### Generate reactors

[Read documentation with examples](./docs/advanced-usage.md#generate-reactors)

```shell
php artisan make:event-sourcing-domain Animal --reactor=1
```

#### Generate failed events

[Read documentation with examples](./docs/advanced-usage.md#generate-failed-events)

```shell
php artisan make:event-sourcing-domain Animal --failed-events=1
```

#### Generate notifications

[Read documentation with examples](./docs/advanced-usage.md#generate-notifications)

```shell
php artisan make:event-sourcing-domain Animal --notifications=<NOTIFICATIONS>
```

#### Specify indentation

[Read documentation with examples](./docs/advanced-usage.md#specify-the-indentation)

```shell
php artisan make:event-sourcing-domain Animal --indentation=2
```

#### Specify the path of root folder

[Read documentation with examples](./docs/advanced-usage.md#specify-the-path-of-root-folder)

```shell
php artisan make:event-sourcing-domain Animal --root=src
```

## Limitations and future enhancements

### Blueprint column types

[Read documentation](./docs/migrations.md#unsupported-column-types)
