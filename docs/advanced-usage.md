# Advanced options

[Back to README](./../README.md)

## Table of Contents

- [Specify primary key](#specify-primary-key)
- [Generate aggregates](#generate-aggregates)
- [Generate reactors](#generate-reactors)
- [Generate failed events](#generate-failed-events)
- [Generate notifications](#generate-notifications)
- [Specify the indentation](#specify-the-indentation)
- [Specify the path of root folder](#specify-the-path-of-root-folder)

## Specify primary key

[⬆️ Go to TOC](#table-of-contents)

Primary key can be `uuid` or `id`.

Use option `--primary-key=[uuid|id]` to choose it, or answer the interactive question.

**Important:** when using a [migration](/docs/migrations.md), the primary key will be automatically inferred.

If primary key `id` is preferred, [aggregates](#generate-aggregates) will not be available.

### Example

Generate a domain using `id` as primary key:

```shell
php artisan make:event-sourcing-domain Animal --primary-key=id
```

## Generate aggregates

[⬆️ Go to TOC](#table-of-contents)

*Read more about aggregates
in [Spatie documentation](https://spatie.be/docs/laravel-event-sourcing/v7/using-aggregates/writing-your-first-aggregate).*

Aggregates can be generated only if primary key is `uuid`.

Use option `--aggregate=[0|1]`, or answer interactive question.

### Example

Generate aggregates:

```shell
php artisan make:event-sourcing-domain Animal --aggregate=1 --primary-key=uuid
```

If aggregates have been generated, actions will automatically use them.

## Generate reactors

[⬆️ Go to TOC](#table-of-contents)

*Read more about reactors
in [Spatie documentation](https://spatie.be/docs/laravel-event-sourcing/v7/using-reactors/writing-your-first-reactor).*

Use option `--reactor=[0|1]`.

### Example

Generate reactors:

```shell
php artisan make:event-sourcing-domain Animal --reactor=1
```

Reactors will be generated for all events, including [failed ones](#generate-failed-events) when enabled with option
`--failed-events=1`.

## Generate failed events

[⬆️ Go to TOC](#table-of-contents)

The command can generate create / update / delete failed events.

Use option `--failed-events=[0|1]`.

### Example

Generate failed events:

```shell
php artisan make:event-sourcing-domain Animal --failed-events=1
```

The following events will be created

```
AnimalCreationFailed
AnimalDeletionFailed
AnimalUpdateFailed
```

If [notifications](#generate-notifications) are created as well using option `--notification=VALUE`, a failed
notification for each failed event will be automatically created.

## Generate notifications

[⬆️ Go to TOC](#table-of-contents)

The command supports 4 types of notifications:

- database
- mail
- Slack
- Teams

Use option `--notifications=[database,mail,slack,teams]`. Notifications must be separated by comma.

When notifications are created, one or more concerns (traits) will be created as well in `Notifications/Concerns`
folder, for shared properties and formatting.

### Examples

Generate automatically database notifications:

```shell
php artisan make:event-sourcing-domain Animal --notifications=database
```

Generate automatically Teams notifications:

```shell
php artisan make:event-sourcing-domain Animal --notifications=teams
```

Generate automatically mail and Slack notifications:

```shell
php artisan make:event-sourcing-domain Animal --notifications=mail,slack
```

## Specify the indentation

[⬆️ Go to TOC](#table-of-contents)

Default indentation of generated files is 4 space.

Use option `--indentation=NUMBER`.

### Example

```shell
php artisan make:event-sourcing-domain Animal --indentation=2
```

This setup will use 2 space as indentation.

## Specify the path of root folder

[⬆️ Go to TOC](#table-of-contents)

It is possible to specify the App folder, e.g. if a domain must be created in unit tests folder or in a package (root is
`src`).

Default root folder is `app`.

Use option `--root=VALUE`.

### Example: Generate domain in `src` folder for a package

Generate domain in `src` folder:

```shell
php artisan make:event-sourcing-domain Animal --root=src
```

Directory structure

```
src/
├── Domain/
│   └── Animal/
│       ├── Actions/
│       │   ├── CreateAnimal
│       │   ├── DeleteAnimal
│       │   └── UpdateAnimal
│       ├── Aggregates/
│       │   └── AnimalAggregate
│       ├── DataTransferObjects/
│       │   └── AnimalData
│       ├── Events/
│       │   ├── AnimalCreated
│       │   ├── AnimalDeleted
│       │   └── AnimalUpdated
│       ├── Projections/
│       │   └── Animal
│       ├── Projectors/
│       │   └── AnimalProjector
│       └── Reactors/
│           └── AnimalReactor
└── etc.
```

### Example: Generate domain in `tests/Unit` folder

Generate domain in `tests/Unit` folder:

```shell
php artisan make:event-sourcing-domain Animal --root=tests/Unit
```

Directory structure

```
tests/
└── Unit/
    ├── Domain/
    │   └── Animal/
    │       ├── Actions/
    │       │   ├── CreateAnimal
    │       │   ├── DeleteAnimal
    │       │   └── UpdateAnimal
    │       ├── Aggregates/
    │       │   └── AnimalAggregate
    │       ├── DataTransferObjects/
    │       │   └── AnimalData
    │       ├── Events/
    │       │   ├── AnimalCreated
    │       │   ├── AnimalDeleted
    │       │   └── AnimalUpdated
    │       ├── Projections/
    │       │   └── Animal
    │       ├── Projectors/
    │       │   └── AnimalProjector
    │       └── Reactors/
    │           └── AnimalReactor
    └── etc.
```

