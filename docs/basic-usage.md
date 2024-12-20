# Basic usage

[Back to README](./../README.md)

## Table of Contents

- [Sample](#sample)
  - [Run command interactively](#run-command-interactively)
  - [Generated directory structure and files](#generated-directory-structure-and-files)
  - [Sample code](#sample-code)

## Sample

Default mode is based on interactive command line.

In this example, `uuid` will be used as primary key, with an aggregate class.

### Run command interactively

[⬆️ Go to TOC](#table-of-contents)

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

Do you want to create an Aggregate class?
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
| Create Aggregate class     | yes         |
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

### Generated directory structure and files

[⬆️ Go to TOC](#table-of-contents)

Directory structure generated (using `uuid` as primary key)

```
app/
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

### Sample code

[⬆️ Go to TOC](#table-of-contents)

If Spatie event sourcing is configured to auto-discover projectors, the following code is immediately usable:

```php
use App\Domain\Animal\Actions\CreateAnimal;
use App\Domain\Animal\DataTransferObjects\AnimalData;
use App\Domain\Animal\Projections\Animal;

# This will create a record in 'animals' table, using projector AnimalProjector
(new CreateAnimal)(new AnimalData(
  name: 'tiger',
  age: 7
));

# Retrieve record
$animal = Animal::query()->where('name', 'tiger')->first();
```
