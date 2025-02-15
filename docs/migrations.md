# Migrations

[Back to README](./../README.md)

## Table of Contents

- [Using migrations](#using-migrations)
- [Generate a domain using existing migration](#generate-a-domain-using-existing-migration)
- [Generate a domain using update migration](#generate-a-domain-using-update-migration)
- [Limitations](#limitations)
    - [Unsupported column types](#unsupported-column-types)

## Using migrations

[⬆️ Go to TOC](#table-of-contents)

Command can generate a full domain directory structure starting from an
existing [database migration](https://laravel.com/docs/11.x/migrations).

The command will parse the migration and map all fields in the data transfer object, projection and projector.

**Important: the command can process _only_ "create" migrations. Other migrations that modify table structure will be
skipped.**

## Generate a domain using existing migration

[⬆️ Go to TOC](#table-of-contents)

Command can generate a full domain directory structure starting from an existing migration.

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

In this example, `id` will be used as primary key. No aggregate will be available.

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
| Create Aggregate class     | no                                         |
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
app/
├── Domain/
│   └── Animal/
│       ├── Actions/
│       │   ├── CreateTiger
│       │   ├── DeleteTiger
│       │   └── UpdateTiger
│       ├── DataTransferObjects/
│       │   └── TigerData
│       ├── Events/
│       │   ├── TigerCreated
│       │   ├── TigerCreationFailed
│       │   ├── TigerDeleted
│       │   ├── TigerDeletionFailed
│       │   ├── TigerUpdateFailed
│       │   └── TigerUpdated
│       ├── Notifications/
│       │   ├── Concerns/
│       │   │   ├── HasDataAsArray
│       │   │   └── HasSlackNotification
│       │   ├── TigerCreated
│       │   ├── TigerCreationFailed
│       │   ├── TigerDeleted
│       │   ├── TigerDeletionFailed
│       │   ├── TigerUpdateFailed
│       │   └── TigerUpdated
│       ├── Projections/
│       │   └── Tiger
│       └── Projectors/
│           └── TigerProjector
└── etc.

tests/
├── Unit/
│   └── Domain/
│       └── Animal/
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

## Generate a domain using update migration

[⬆️ Go to TOC](#table-of-contents)

Command can generate a full domain directory structure starting from an update migration.

E.g. create migration `2024_10_01_112344_create_tigers_table.php`

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

E.g. update migration `2024_10_07_031123_update_tigers_table.php`

```php
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tigers', function (Blueprint $table) {
            $table->float('age');
            $table->string('colour');
        });
    }
    
    // etc.
};
```

In this example, `id` will be used as primary key. No aggregate will be available.

**Important:** to parse both create and update migrations, pass the _table name_ in the `--migration` parameter. That will
search for all migrations containing that name. Words `create` and `update` are reserved and cannot be passed.

It is possible to specify the migration interactively or, more efficiently, passing it to command options. Please notice
that the migration filename timestamp is not needed:

```shell
php artisan make:event-sourcing-domain Tiger --domain=Animal --migration=tigers --notifications=slack --failed-events=1 --reactor=0 --unit-test
```

```
Your choices:

| Option                     | Choice                                     |
|----------------------------|--------------------------------------------|
| Model                      | Tiger                                      |
| Domain                     | Animal                                     |
| Namespace                  | Domain                                     |
| Use migration              | 2024_10_01_112344_create_animals_table.php |
|                            | 2024_10_07_031123_update_tigers_table.php  |
| Primary key                | id                                         |
| Create Aggregate class     | no                                         |
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


## Limitations

[⬆️ Go to TOC](#table-of-contents)

### Unsupported column types

[⬆️ Go to TOC](#table-of-contents)

The following column types are not yet supported:

- primary composite keys e.g. `$table->primary(['id', 'parent_id']);`
- `binary`
- `foreignIdFor`
- `foreignUlid`
- `geography` (from Laravel 11.x)
- `geometry`
- `morphs`
- `nullableMorphs`
- `nullableUlidMorphs`
- `nullableUuidMorphs`
- `set`
- `ulidMorphs`
- `uuidMorphs`
- `ulid`

If the database migration contains any of those:

- a warning will be generated in command line output for each of those
- a @todo comment will be added for each of those in the data transfer object, projection and projector.

E.g. migration `2024_10_01_112344_create_lions_table.php`

```shell
php artisan make:event-sourcing-domain Lion --domain=Animal --migration=create_lions_table
```

```php
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index();
            $table->int('age');
            $table->ulidMorphs('taggable');
            $table->timestamps();
        });
    }
    
    // etc.
};
```

Data Transfer Object

```php
namespace App\Domain\Animal\DataTransferObjects;

class LionData
{
    public function __construct(
        public string $name,
        public int $age,
        // @todo public ulidMorphs $taggable, column type is not yet supported,
    ) {}

    // etc.
}
```

Projection

```php
namespace App\Domain\Animal\Projections;

/**
 * @property int $id
 * @property string $name
 * @property int $age
 */
class Lion extends Projection
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'age',
        // @todo 'taggable', column type 'ulidMorphs' is not yet supported
    ];

    protected $casts = [
        'id' => 'int',
        'name' => 'string',
        'age' => 'int',
        // @todo 'taggable' => 'ulidMorphs', column type is not yet supported
    ];

    // etc.
}
```

Projector

```php
namespace App\Domain\Animal\Projectors;

class AnimalProjector extends Projector
{
    public function onLionCreated(LionCreated $event): void
    {
        try {
            (new Animal)->writeable()->create([
                'name' => $event->lionData->name,
                'age' => $event->lionData->age,
                // @todo 'taggable' => $event->lionData->taggable, column type 'ulidMorphs' is not yet supported
            ]);
        } catch (Exception $e) {
            Log::error('Unable to create animal', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }
    
    // etc.
}
```