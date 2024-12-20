<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Exception;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\Concerns\WithMockPackages;
use Tests\TestCase;

class MakeEventSourcingDomainCommandUnitTestsTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

    #[Test]
    public function it_can_create_a_model_and_domain_with_unit_tests()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--primary-key' => 'uuid', '--unit-test' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', '?int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to create an Aggregate class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create Aggregate class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'yes'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('WARN  PHPUnit package has not been installed. Run what follows:')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, domain: $domain, modelProperties: $properties, createUnitTest: true);
    }

    #[Test]
    public function it_can_create_a_model_and_domain_with_unit_tests_using_id_as_primary_key()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--primary-key' => 'id', '--unit-test' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', '?int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create Aggregate class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'yes'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, domain: $domain, useUuid: false, modelProperties: $properties, createUnitTest: true);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument_using_all_blueprint_column_types_and_unit_test()
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
            ':primary' => ['bigIncrements' => 'id'],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $model) => $this->columnTypeToBuiltInType($type)." $model"));

        $this->createMockCreateMigration('animal', $properties, $options);
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--migration' => 'create_animals_table', '--reactor' => 0, '--unit-test' => true])
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
            modelProperties: $properties,
            createUnitTest: true
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_unit_tests_without_phpunit_package()
    {
        $this->hidePhpunitPackage();

        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => '?int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--primary-key' => 'uuid', '--unit-test' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', '?int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to create an Aggregate class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create Aggregate class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'yes'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('WARN  PHPUnit package has not been installed. Run what follows:')
            ->expectsOutputToContain('WARN  composer require phpunit/phpunit --dev')
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, domain: $domain, modelProperties: $properties, createUnitTest: true);
    }
}
