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
            ->expectsQuestion('Do you want to create an Aggregate class?', true)
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
                    ['Create Aggregate class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'no'],
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

        $createMigration = basename($this->createMockCreateMigration('animal', $properties, [MigrationOptionInterface::PRIMARY_KEY => 'id']));
        $updateMigration = basename($this->createMockUpdateMigration('animal', $properties));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', true)
            ->expectsChoice(
                'Select database migration',
                $createMigration,
                [$createMigration, $updateMigration],
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
                    ['Use migration', $createMigration],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'no'],
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
            createAggregate: false,
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

        $createMigration = basename($this->createMockCreateMigration('animal', $properties));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--migration' => 'create_animals_table'])
            ->expectsQuestion('Which is the name of the domain?', $model)
            // Options
            ->expectsQuestion('Do you want to create an Aggregate class?', true)
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
                    ['Use migration', $createMigration],
                    ['Primary key', 'uuid'],
                    ['Create Aggregate class', 'yes'],
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
            'jsonb_field' => 'jsonb',
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

        $createMigration = basename($this->createMockCreateMigration('animal', $properties, $options));
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
                    ['Use migration', $createMigration],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
                    ['Create Reactor class', 'no'],
                    ['Create PHPUnit tests', 'no'],
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
            createAggregate: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_ignoring_unsupported_blueprint_column_types()
    {
        $validProperties = [
            'name' => 'string',
        ];

        $ignoredProperties = [
            'binary_field' => 'binary',
            'foreign_id_for_field' => 'foreignIdFor',
            'foreign_ulid_field' => 'foreignUlid',
            'geography_field' => 'geography',
            'geometry_field' => 'geometry',
            'nullable_morphs_field' => 'nullableMorphs',
            'nullable_ulid_morphs_field' => 'nullableUlidMorphs',
            'nullable_uuid_morphs_field' => 'nullableUuidMorphs',
            'set_field' => 'set',
            'ulid_morphs_field' => 'ulidMorphs',
            'uuid_morphs_field' => 'uuidMorphs',
            'ulid_field' => 'ulid',
        ];

        $properties = array_merge($validProperties, $ignoredProperties);

        $options = [
            MigrationOptionInterface::PRIMARY_KEY => ['primary' => [['id', 'parent_id']]],
        ];

        $expectedPrintedProperties = array_values(Arr::map($validProperties, fn ($type, $model) => "$type $model"));

        $createMigration = basename($this->createMockCreateMigration('animal', $properties, $options));
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', [
            'model' => $model,
            '--domain' => $model,
            '--migration' => 'create_animals_table',
            '--aggregate' => 0,
            '--reactor' => 0,
        ])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', $createMigration],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
                    ['Create Reactor class', 'no'],
                    ['Create PHPUnit tests', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->expectsOutputToContain('WARN  Composite keys are not supported for primary key.')
            ->expectsOutputToContain('WARN  Type binary is not supported for column binary_field.')
            ->expectsOutputToContain('WARN  Type foreignIdFor is not supported for column foreign_id_for_field.')
            ->expectsOutputToContain('WARN  Type foreignUlid is not supported for column foreign_ulid_field.')
            ->expectsOutputToContain('WARN  Type geography is not supported for column geography_field.')
            ->expectsOutputToContain('WARN  Type geometry is not supported for column geometry_field.')
            ->expectsOutputToContain('WARN  Type nullableMorphs is not supported for column nullable_morphs_field.')
            ->expectsOutputToContain('WARN  Type nullableUuidMorphs is not supported for column nullable_uuid_morphs_field.')
            ->expectsOutputToContain('WARN  Type set is not supported for column set_field.')
            ->expectsOutputToContain('WARN  Type ulidMorphs is not supported for column ulid_morphs_field.')
            ->expectsOutputToContain('WARN  Type uuidMorphs is not supported for column uuid_morphs_field.')
            ->expectsOutputToContain('WARN  Type ulid is not supported for column ulid_field.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            migration: 'create_animals_table',
            createAggregate: false,
            createReactor: false,
            useUuid: false,
            modelProperties: $validProperties,
            ignoredProperties: $ignoredProperties,
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_ignoring_skipped_blueprint_column_types()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $options = [
            MigrationOptionInterface::SOFT_DELETES => 'softDeletesTz',
            MigrationOptionInterface::INJECTS => [
                ['unique' => 'name'],
                ['fullText' => 'name'],
                ['index' => 'name'],
                ['rawIndex' => ['name', 'name']],
                ['spatialIndex' => 'name'],
                ['foreign' => 'dummyId', 'references' => 'id', 'on' => 'dummy', 'onDelete' => 'cascade'],
            ],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $model) => $this->columnTypeToBuiltInType($type)." $model"));

        $createMigration = basename($this->createMockCreateMigration('animal', $properties, $options));
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
                    ['Use migration', $createMigration],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
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
            createAggregate: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_update_migration()
    {
        $createProperties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];
        $createMigration = basename($this->createMockCreateMigration('animal', $createProperties));

        $updateProperties = [
            'age' => 'float',
            'new_field' => '?string',
        ];
        $updateMigration = basename($this->createMockUpdateMigration('animal', $updateProperties));

        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => 'animals', '--aggregate' => 1, '--reactor' => 0])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', $createMigration."\n".$updateMigration],
                    ['Primary key', 'uuid'],
                    ['Create Aggregate class', 'yes'],
                    ['Create Reactor class', 'no'],
                    ['Create PHPUnit tests', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map(array_merge($createProperties, $updateProperties), fn ($type, $model) => "$type $model"))],
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
            migration: 'animal',
            createReactor: false,
            modelProperties: array_merge($createProperties, $updateProperties),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_update_migration_using_id_as_primary_key()
    {
        $createProperties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];
        $createMigration = basename($this->createMockCreateMigration('animal', $createProperties, [MigrationOptionInterface::PRIMARY_KEY => 'id']));

        $updateProperties = [
            'age' => 'float',
            'new_field' => '?string',
        ];
        $updateMigration = basename($this->createMockUpdateMigration('animal', $updateProperties));

        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => 'animals', '--reactor' => 0])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', $createMigration."\n".$updateMigration],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
                    ['Create Reactor class', 'no'],
                    ['Create PHPUnit tests', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map(array_merge($createProperties, $updateProperties), fn ($type, $model) => "$type $model"))],
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
            migration: 'animal',
            createAggregate: false,
            createReactor: false,
            useUuid: false,
            modelProperties: array_merge($createProperties, $updateProperties),
        );
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
