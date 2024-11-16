# Laravel event sourcing generator

![build-test](coverage.svg)

## About

Laravel event sourcing generator adds a new command that can generate a full domain directory structure
for [Spatie event sourcing](https://github.com/spatie/laravel-event-sourcing).

```shell
php artisan make:event-sourcing-domain
```

## Compatibility

| What                                                                        | Version         |
|-----------------------------------------------------------------------------|-----------------|
| PHP                                                                         | 8.2             |
| [Laravel](https://github.com/laravel/laravel)                               | 10.x / 11.x (*) |
| [Spatie's event sourcing](https://github.com/spatie/laravel-event-sourcing) | 7.x             |

> (*) Package has been tested in Laravel 10, even it is not officially released for that version.

## Installation

### Composer setup

> **Important:** this package is not yet available as a stable version. Until that happens, you need to
> change your composer settings to accept unstable packages:

```bash
composer config prefer-stable false
```

### Install

```shell
composer require albertoarena/laravel-event-sourcing-generator
```

## Usage

```text
php artisan make:event-sourcing-domain <model>
  [--domain=<domain>]                            # The name of the domain
  [--namespace=<namespace>]                      # The namespace or root folder
  [--migration=<existing_migration_filename>]    # Indicate any existing migration for the model, with or without timestamp prefix
  [--aggregate-root=<0|1>]                       # Indicate if aggregate root must be created or not (accepts 0 or 1)
  [--reactor=<0|1>]                              # Indicate if reactor must be created or not (accepts 0 or 1)
  [--unit-test]                                  # Indicate if PHPUnit tests must be created
  [--primary-key=<uuid|id>]                      # Indicate which is the primary key (uuid, id)
  [--indentation=<indent>]                       # Indentation spaces
  [--failed-events=<0|1>]                        # Indicate if failed events must be created (accepts 0 or 1)
  [--notifications=<mail,no,slack,teams>]        # Indicate if notifications must be created, comma separated (accepts mail,no,slack,teams)
```

Generate a model with same name of domain

```shell
# App/Domain/Animal/Actions/CreateAnimal
php artisan make:event-sourcing-domain Animal
```

Generate a model with different domain

```shell
# App/Domain/Animal/Actions/CreateTiger
php artisan make:event-sourcing-domain Tiger --domain=Animal
```

Generate a model with different domain and namespace 

```shell
# App/CustomDomain/Animal/Actions/CreateTiger
php artisan make:event-sourcing-domain Tiger \
  --domain=Animal \
  --namespace=CustomDomain 
```

Generate a model from existing migration with PHPUnit tests

```shell
# App/Domain/Animal/Actions/CreateAnimal
php artisan make:event-sourcing-domain Animal \
  --migration=create_animal_table \
  --unit-test
```

Generate a model from existing migration with failed events and mail / Slack notifications

```shell
# App/Domain/Animal/Actions/CreateAnimal
php artisan make:event-sourcing-domain Animal \
  --migration=create_animal_table \
  --failed-events=1 \
  --notifications=mail,slack
```

Show help

```shell
php artisan help make:event-sourcing-domain
```

### Generate domain structure using interactive command line

Default mode is based on interactive command line.

In this example, `uuid` will be used as primary key with an aggregate root class.

```shell
php artisan make:event-sourcing-domain Animal
```

```
Which is the name of the domain? [Animal]
> Animal

Do you want to import properties from existing database migration?
> no

Do you want to specify model properties?
> yes

Property name (exit to quit)?
> name

Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)
> string

Property name (exit to quit)?
> age

Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)
> int

Property name (exit to quit)?
> exit

Do you want to use uuid as model primary key?
> yes

Do you want to create an AggregateRoot class?
> yes

Do you want to create a Reactor class?
> yes

Your choices:

| Option                     | Choice      |
|----------------------------|-------------|
| Model                      | Animal      |
| Domain                     | Animal      |
| Namespace                  | Domain      |
| Use migration              | no          |
| Primary key                | uuid        |
| Create AggregateRoot class | yes         |
| Create Reactor class       | yes         |
| Create PHPUnit tests       | no          |
| Create failed events       | no          |
| Model properties           | string name |
|                            | int age     |
| Notifications              | no          |

Do you confirm the generation of the domain?
> yes

Domain [Animal] with model [Animal] created successfully.
```

Directory structure generated (using `uuid` as primary key)

```
app
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

If Spatie event sourcing is configured to auto-discover projectors, that is immediately usable:

```php
use App\Domain\Animal\Actions\CreateAnimal;
use App\Domain\Animal\DataTransferObjects\AnimalData;
use App\Domain\Animal\Projections\Animal;

# This will create a record in 'animal' table, using projector AnimalProjector
(new CreateAnimal())(new AnimalData(
  name: 'tiger',
  age: 7
));

# Retrieve record
$animal = Animal::query()->where('name', 'tiger')->first();
```

### Generate domain using existing migration, failed events, notifications and PHPUnit tests

Command can generate a full domain directory structure starting from an existing migration.

**Important: the command can process _only_ "create" migrations. Other migrations that modify table structure will be
skipped.**

E.g. migration `2024_10_01_112344_create_tigers_table.php`

```php
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tigers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index();
            $table->int('age');
            $table->json('meta');
            $table->timestamps();
        });
    }
    
    // etc.
};
```

In this example, `id` will be used as primary key. No aggregate root will be available.

It is possible to specify the migration interactively or, more efficiently, passing it to command options. Please notice
that the migration filename timestamp is not needed:

```shell
php artisan make:event-sourcing-domain Tiger --domain=Animal --migration=create_tigers_table --notifications=slack --failed-events=1 --reactor=0 --unit-test
```

```
Your choices:

| Option                     | Choice                                     |
|----------------------------|--------------------------------------------|
| Model                      | Tiger                                      |
| Domain                     | Animal                                     |
| Namespace                  | Domain                                     |
| Use migration              | 2024_10_01_112344_create_animals_table.php |
| Primary key                | id                                         |
| Create AggregateRoot class | no                                         |
| Create Reactor class       | no                                         |
| Create PHPUnit tests       | yes                                        |
| Create failed events       | yes                                        |
| Model properties           | string name                                |
|                            | int age                                    |
|                            | array meta                                 |
| Notifications              | yes                                        |

Do you confirm the generation of the domain?
> yes

Domain [Animal] with model [Tiger] created successfully.
```

Directory structure generated (using `id` as primary key)

```
app
├── Domain
│   └── Animal
│       ├── Actions
│       │   ├── CreateTiger
│       │   ├── DeleteTiger
│       │   └── UpdateTiger
│       ├── DataTransferObjects
│       │   └── TigerData
│       ├── Events
│       │   ├── TigerCreated
│       │   ├── TigerCreationFailed
│       │   ├── TigerDeleted
│       │   ├── TigerDeletionFailed
│       │   ├── TigerUpdateFailed
│       │   └── TigerUpdated
│       ├── Notifications
│       │   ├── Concerns
│       │   │   ├── HasDataAsArray
│       │   │   └── HasSlackNotification
│       │   ├── TigerCreated
│       │   ├── TigerCreationFailed
│       │   ├── TigerDeleted
│       │   ├── TigerDeletionFailed
│       │   ├── TigerUpdateFailed
│       │   └── TigerUpdated
│       ├── Projections
│       │   └── Tiger
│       └── Projectors
│           └── TigerProjector
└── etc.

tests
├── Unit
│   └── Domain
│       └── Animal
│           └── TigerTest.php
└── etc.
```

If Spatie event sourcing is configured to auto-discover projectors, that is immediately usable:

```php
use App\Domain\Animal\Actions\CreateTiger;
use App\Domain\Animal\DataTransferObjects\TigerData;
use App\Domain\Animal\Projections\Tiger;

# This will create a record in 'tigers' table, using projector TigerProjector
(new CreateTiger())(new TigerData(
  name: 'tiger',
  age: 7,
  meta: []
));

# Retrieve record
$tiger = Tiger::query()->where('name', 'tiger')->first();
```

## Options

### Specify domain

```shell
php artisan make:event-sourcing-domain Tiger --domain=Animal
php artisan make:event-sourcing-domain Lion --domain=Animal
```

This will create multiple models in the same domain

```
app
├── Domain
│   └── Animal
│       ├── Actions
│       │   ├── CreateLion
│       │   ├── CreateTiger
│       │   ├── DeleteLion
│       │   ├── DeleteTiger
│       │   ├── UpdateLion
│       │   └── UpdateTiger
│       ├── DataTransferObjects
│       │   ├── LionData
│       │   └── TigerData
│       ├── Events
│       │   ├── LionCreated
│       │   ├── TigerCreated
│       │   ├── LionDeleted
│       │   ├── TigerDeleted
│       │   ├── LionDeleted
│       │   └── TigerUpdated
│       ├── Projections
│       │   ├── Lion
│       │   └── Tiger
│       ├── Projectors
│       │   ├── LionProjector
│       │   └── TigerProjector
│       ├── LionAggregateRoot
│       └── TigerAggregateRoot
└── etc.
```


### Specify namespace

```shell
php artisan make:event-sourcing-domain Animal --namespace=CustomDomain
```

This setup will use `CustomDomain` as the namespace

```
app
├── CustomDomain
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
│       └── Projectors
│       │   └── AnimalProjector
│       └── Reactors
│           └── AnimalReactor
└── etc.
```


### Create PHPUnit test

```shell
php artisan make:event-sourcing-domain Animal --unit-test
```

This setup will create a PHPUnit test, already working for create / update / delete events.

```
tests
├── Unit
│   └── Domain
│       └── Animal
│           └── AnimalTest.php
└── etc.
```

### Specify indentation

Default indentation of generated files is 4 space.

```shell
php artisan make:event-sourcing-domain DOMAIN --indentation=2
```

This setup will use 2 space as indentation.

## Limitations and future enhancements

### Blueprint column types

The following column types are not yet supported:

- primary composite keys e.g. `$table->primary(['id', 'parent_id']);`
- `binary`
- `foreignIdFor`
- `foreignUlid`
- `geography`
- `geometry`
- `jsonb`
- `morphs`
- `nullableMorphs`
- `nullableUlidMorphs`
- `nullableUuidMorphs`
- `set`
- `ulidMorphs`
- `uuidMorphs`
- `ulid`

### Future enhancements

- support migrations that update table
- support PHP 8.3

## Develop

Feel free to fork, improve and create a pull request!