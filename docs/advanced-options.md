# Advanced options

[Back to README](./../README.md)

## Table of Contents

- [Generate aggregates](#generate-aggregates)
- [Generate reactors](#generate-reactors)
- [Generate failed events](#generate-failed-events)
- [Generate notifications](#generate-notifications)
- [Specify the indentation](#specify-the-indentation)
- [Specify the path of root folder](#specify-the-path-of-root-folder)
  - [Generate domain in `src` folder for a package](#generate-domain-in-src-folder-for-a-package)
  - [Generate domain in `tests/Unit` folder](#generate-domain-in-testsunit-folder)


## Generate aggregates

[⬆️ Go to TOC](#table-of-contents)

Aggregates can be generated only if primary key is `uuid`.

Read [Spatie documentation](https://spatie.be/docs/laravel-event-sourcing/v7/using-aggregates/writing-your-first-aggregate) for further details.

```shell
php artisan make:event-sourcing-domain Animal --aggregate=1
```

If aggregates have been generated, actions will use them.

## Generate reactors

[⬆️ Go to TOC](#table-of-contents)

Aggregates can be generated only if primary key is `uuid`.

Read [Spatie documentation](https://spatie.be/docs/laravel-event-sourcing/v7/using-reactors/writing-your-first-reactor) for further details.

```shell
php artisan make:event-sourcing-domain Animal --reactor=1
```

Reactors will be generated for all events, including [failed ones](#generate-failed-events) when enabled with `--failed-events=1`


## Generate failed events

[⬆️ Go to TOC](#table-of-contents)

The command can generate create / update / delete failed events.

```shell
php artisan make:event-sourcing-domain Animal --failed-events=1
```


## Generate notifications

[⬆️ Go to TOC](#table-of-contents)

The command supports 3 types of notifications:

- mail
- Slack
- Teams

```shell
php artisan make:event-sourcing-domain Animal --notifications=<NOTIFICATIONS>
```


### Examples

Generate automatically Teams notifications

```shell
php artisan make:event-sourcing-domain Animal --notifications=teams
```

Generate automatically mail and Slack notifications

```shell
php artisan make:event-sourcing-domain Animal --notifications=mail,slack
```


## Specify the indentation

[⬆️ Go to TOC](#table-of-contents)

Default indentation of generated files is 4 space.

```shell
php artisan make:event-sourcing-domain Animal --indentation=2
```

This setup will use 2 space as indentation.

## Specify the path of root folder

[⬆️ Go to TOC](#table-of-contents)

Default root folder is `app`.

It is possible to specify the App folder, e.g. if a domain must be created in unit tests folder or in a package (root is
`src`).

### Generate domain in `src` folder for a package

[⬆️ Go to TOC](#table-of-contents)

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

### Generate domain in `tests/Unit` folder

[⬆️ Go to TOC](#table-of-contents)

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

