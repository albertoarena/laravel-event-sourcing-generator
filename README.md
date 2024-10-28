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
php artisan make:event-sourcing-domain <model> [--domain=<domain>]
  [--namespace=<namespace>]
  [--migration=<existing_migration_filename>]
  [--aggregate-root=<0|1>]
  [--reactor=<0|1>]
  [--unit-test]
  [--primary-key=<uuid|id>]
  [--indentation=<indent>]
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
php artisan make:event-sourcing-domain Tiger --domain=Animal --namespace=CustomDomain 
```

Generate a model from existing migration with unit test

```shell
# App/Domain/Animal/Actions/CreateAnimal
php artisan make:event-sourcing-domain Animal --migration=create_animal_table --unit-test
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
| Model properties           | string name |
|                            | int age     |

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

### Generate domain using existing migration

Command can generate a full domain directory structure starting from an existing migration.

**Important: the command can process _only_ "create" migrations. Other migrations that modify table structure will be
skipped.**

E.g. migration `2024_10_01_112344_create_animals_table.php`

```php
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
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
php artisan make:event-sourcing-domain Animal --migration=create_animals_table
```

**Note:** migration will be asked interactively if not specified as command line option.

```shell
php artisan make:event-sourcing-domain Animal --migration=create_animals_table
```

```
Which is the name of the domain? [Animal]
> Animal

Do you want to create a Reactor class?
> no

Your choices:

| Option                     | Choice                                     |
|----------------------------|--------------------------------------------|
| Model                      | Animal                                     |
| Domain                     | Animal                                     |
| Namespace                  | Domain                                     |
| Use migration              | 2024_10_01_112344_create_animals_table.php |
| Primary key                | id                                         |
| Create AggregateRoot class | no                                         |
| Create Reactor class       | no                                         |
| Model properties           | string name                                |
|                            | int age                                    |
|                            | array meta                                 |

Do you confirm the generation of the domain?
> yes

Domain [Animal] with model [Animal] created successfully.
```

Directory structure generated (using `id` as primary key)

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
│       └── Projectors
│           └── AnimalProjector
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
  age: 7,
  meta: []
));

# Retrieve record
$animal = Animal::query()->where('name', 'tiger')->first();
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


### Create unit test

```shell
php artisan make:event-sourcing-domain Animal --unit-test
```

This setup will create a unit test, already working for create / update / event events.

```shell
tests
├── Domain
│   └── Animal
│       └── AnimalTest.php
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