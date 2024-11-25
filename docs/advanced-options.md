# Advanced options

[Back to README](./../README.md)

## Table of Contents

- [Specify the indentation](#specify-the-indentation)
- [Specify the path of root folder](#specify-the-path-of-root-folder)
  - [Generate domain in `src` folder for a package](#generate-domain-in-src-folder-for-a-package)
  - [Generate domain in `tests/Unit` folder](#generate-domain-in-testsunit-folder)


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
src
├── Domain
│   └── Animal
│       ├── Actions
│       │   ├── CreateAnimal
│       │   ├── DeleteAnimal
│       │   └── UpdateAnimal
│       ├── DataTransferObjects
│       │   └── AnimalData
│       ├── Events
│       │   ├── AnimalCreated
│       │   ├── AnimalDeleted
│       │   └── AnimalUpdated
│       ├── Projections
│       │   └── Animal
│       ├── Projectors
│       │   └── AnimalProjector
│       ├── Reactors
│       │   └── AnimalReactor
│       └── AnimalAggregateRoot
└── etc.
```

### Generate domain in `tests/Unit` folder

[⬆️ Go to TOC](#table-of-contents)

```shell
php artisan make:event-sourcing-domain Animal --root=tests/Unit
```

Directory structure

```
tests
└── Unit
    ├── Domain
    │   └── Animal
    │       ├── Actions
    │       │   ├── CreateAnimal
    │       │   ├── DeleteAnimal
    │       │   └── UpdateAnimal
    │       ├── DataTransferObjects
    │       │   └── AnimalData
    │       ├── Events
    │       │   ├── AnimalCreated
    │       │   ├── AnimalDeleted
    │       │   └── AnimalUpdated
    │       ├── Projections
    │       │   └── Animal
    │       ├── Projectors
    │       │   └── AnimalProjector
    │       ├── Reactors
    │       │   └── AnimalReactor
    │       └── AnimalAggregateRoot
    └── etc.
```

