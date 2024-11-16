<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\Concerns\WithMockPackages;
use Tests\Domain\Migrations\Contracts\MigrationOptionInterface;
use Tests\TestCase;

class MakeEventSourcingDomainCommandMigrationsTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_using_uuid_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $migrationPath = basename($this->createMockCreateMigration('animal', $properties));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', true)
            ->expectsChoice(
                'Select database migration',
                $migrationPath,
                [$migrationPath],
            )
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_using_id_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $migrationPath = basename($this->createMockCreateMigration('animal', $properties, [':primary' => 'id']));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', true)
            ->expectsChoice(
                'Select database migration',
                $migrationPath,
                [$migrationPath],
            )
            // Options
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            useUuid: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $this->createMockCreateMigration('animal', $properties);
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--migration' => 'create_animals_table'])
            ->expectsQuestion('Which is the name of the domain?', $model)
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_using_all_blueprint_column_types()
    {
        $properties = [
            'bool_field' => 'bool',
            'boolean_field' => 'boolean',
            'big_integer_field' => 'bigInteger',
            'integer_field' => 'int',
            'foreign_id_field' => 'foreignId',
            'medium_integer_field' => 'mediumInteger',
            'small_integer_field' => 'smallInteger',
            'tiny_integer_field' => 'tinyInteger',
            'json_field' => 'json',
            'string_field' => 'string',
            'datetime_tz_field' => 'dateTimeTz',
            'datetime_field' => 'dateTime',
            'timestamp_tz_field' => 'timestampTz',
            'timestamp_field' => 'timestamp',
            'timestamps_tz_field' => 'timestampsTz',
            'time_tz_field' => 'timeTz',
            'time_field' => 'time',
            'char_field' => 'char',
            'enum_field' => 'enum',
            'foreign_uuid_field' => 'foreignUuid',
            'ip_address_field' => 'ipAddress',
            'long_text_field' => 'longText',
            'mac_address_field' => 'macAddress',
            'medium_text_field' => 'mediumText',
            'remember_token_field' => 'rememberToken',
            'text_field' => 'text',
            'tiny_text_field' => 'tinyText',
            'date_field' => 'date',
            'nullable_timestamps_field' => 'nullableTimestamps',
            'nullable_string_field' => '?string',
            'nullable_int_field' => '?int',
            'nullable_float_field' => '?float',
            'uuid_field' => 'uuid',
        ];

        $options = [
            MigrationOptionInterface::PRIMARY_KEY => ['bigIncrements' => 'id'],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $model) => $this->columnTypeToBuiltInType($type)." $model"));

        $this->createMockCreateMigration('animal', $properties, $options);
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--migration' => 'create_animals_table', '--reactor' => 0])
            ->expectsQuestion('Which is the name of the domain?', $model)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'no'],
                    ['Create unit test', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_using_unsupported_blueprint_column_types()
    {
        $validProperties = [
            'name' => 'string',
        ];

        $properties = array_merge(
            $validProperties, [
                'binary_field' => 'binary',
                'foreign_id_for_field' => 'foreignIdFor',
                'foreign_ulid_field' => 'foreignUlid',
                'geography_field' => 'geography',
                'geometry_field' => 'geometry',
                'jsonb_field' => 'jsonb',
                'nullable_morphs_field' => 'nullableMorphs',
                'nullable_ulid_morphs_field' => 'nullableUlidMorphs',
                'nullable_uuid_morphs_field' => 'nullableUuidMorphs',
                'set_field' => 'set',
                'ulid_morphs_field' => 'ulidMorphs',
                'uuid_morphs_field' => 'uuidMorphs',
                'ulid_field' => 'ulid',
            ]
        );

        $expectedPrintedProperties = array_values(Arr::map($validProperties, fn ($type, $model) => "$type $model"));

        $this->createMockCreateMigration('animal', $properties);
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => 'create_animals_table', '--aggregate-root' => 0, '--reactor' => 0])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'no'],
                    ['Create unit test', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $validProperties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_ignoring_indexes_foreign_keys_and_soft_deletes()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $options = [
            MigrationOptionInterface::SOFT_DELETES => 'softDeletesTz',
            MigrationOptionInterface::INJECTS => [
                ['index' => 'name'],
                ['rawIndex' => ['name', 'name']],
                ['spatialIndex' => 'name'],
                ['foreign' => 'dummyId', 'references' => 'id', 'on' => 'dummy', 'onDelete' => 'cascade'],
            ],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $model) => $this->columnTypeToBuiltInType($type)." $model"));

        $this->createMockCreateMigration('animal', $properties, $options);
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--migration' => 'create_animals_table', '--reactor' => 0])
            ->expectsQuestion('Which is the name of the domain?', $model)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    #[Test]
    public function it_cannot_create_a_model_and_domain_with_update_migration()
    {
        $model = 'Animal';
        $properties = [
            'new_field' => '?string',
        ];
        $migrationPath = $this->createMockUpdateMigration('animal', $properties);

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => $migrationPath])
            ->expectsOutputToContain('ERROR  There was an error: Update migration file is not supported.')
            ->assertFailed();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_cannot_create_a_model_and_domain_with_damaged_migration()
    {
        $model = 'Animal';
        $properties = [
            'new_field' => '?string',
        ];
        $migrationPath = $this->createMockCreateMigration('animal', $properties);

        $code = File::get($migrationPath);
        File::put($migrationPath, $code."\nops");

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => $migrationPath])
            ->expectsOutputToContain('ERROR  There was an error: Parser failed: Syntax error, unexpected EOF on line 27')
            ->assertFailed();
    }

    #[Test]
    public function it_cannot_create_a_model_and_domain_with_non_existing_migration()
    {
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_cannot_create_a_model_and_domain_with_migration_using_uuid_as_primary_key_with_id_field_name()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $migrationPath = basename($this->createMockCreateMigration('animal', $properties, [
            MigrationOptionInterface::PRIMARY_KEY => ['uuid' => 'id'], // $table->uuid('id')->primary()
        ]));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', true)
            ->expectsChoice(
                'Select database migration',
                $migrationPath,
                [$migrationPath],
            )
            // Result
            ->expectsOutputToContain('ERROR  There was an error: Primary key is not valid.')
            ->assertFailed();
    }
}
