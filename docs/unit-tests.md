# Unit Tests

[Back to README](./../README.md)

The command can generate automatically PHPUnit test for the domain, that will cover create / update / delete events.

## Table of Contents

- [Basic sample](#basic-sample)
- [Advanced sample: generate domain using existing migration, failed events, notifications and PHPUnit tests](#advanced-sample-generate-domain-using-existing-migration-failed-events-notifications-and-phpunit-tests)

## Basic sample

[⬆️ Go to TOC](#table-of-contents)

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

## Advanced sample: generate domain using existing migration, failed events, notifications and PHPUnit tests

[⬆️ Go to TOC](#table-of-contents)

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