<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Concerns\HasBlueprintColumnType;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use phpmock\mockery\PHPMockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Spatie\EventSourcing\EventSourcingServiceProvider;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\TestCase;

use function class_exists;

class MakeEventSourcingDomainCommandTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;

    protected function hideSpatiePackage(): void
    {
        PHPMockery::mock('Albertoarena\LaravelEventSourcingGenerator\Console\Commands', 'class_exists')
            ->andReturnUsing(function ($class) {
                return ! ($class === EventSourcingServiceProvider::class) && class_exists($class);
            });
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_show_help_for_artisan_command()
    {
        $this->withoutMockingConsoleOutput()
            ->artisan('help make:event-sourcing-domain');

        $output = Artisan::output();
        $this->assertMatchesRegularExpression("/Description:\n\s*Create a new domain for Spatie event sourcing/", $output);
        $this->assertMatchesRegularExpression("/Usage:\n\s*make:event-sourcing-domain \[options] \[--] <name>/", $output);
        $this->assertMatchesRegularExpression("/Arguments:\n\s*name\s*Name of domain/", $output);
        $this->assertMatchesRegularExpression("/\s*-d, --domain\[=DOMAIN]\s*Domain base root \[default: \"Domain\"]/", $output);
        $this->assertMatchesRegularExpression("/\s*-m, --migration\[=MIGRATION]\s*Existing migration for the model, with or without timestamp prefix/", $output);
        $this->assertMatchesRegularExpression("/\s*--aggregate_root\[=AGGREGATE_ROOT]\s*Create aggregate root/", $output);
        $this->assertMatchesRegularExpression("/\s*--reactor\[=REACTOR]\s*Create reactor/", $output);
        $this->assertMatchesRegularExpression("/\s*--indentation\[=INDENTATION]\s*Indentation spaces \[default: \"4\"]/", $output);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_using_id_as_primary_key()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', false)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, useUuid: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_without_properties()
    {
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', false)
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_different_root_domain()
    {
        $rootDomain = 'Domains';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '-d' => $rootDomain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', $rootDomain],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, domainBaseRoot: $rootDomain, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_indentation_argument()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--indentation' => 2])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, modelProperties: $properties, indentation: 2);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_aggregate_root_argument()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this
            ->artisan('make:event-sourcing-domain', ['name' => $domain, '--aggregate_root' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_without_aggregate_root()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', false)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, createAggregateRoot: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_reactor_argument()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this
            ->artisan('make:event-sourcing-domain', ['name' => $domain, '--reactor' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_without_reactor()
    {
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', false)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, createReactor: false, modelProperties: $properties);
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_using_uuid_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $migrationPath = basename($this->createMockMigration('animal', $properties));
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
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
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_using_id_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $migrationPath = basename($this->createMockMigration('animal', $properties, [':primary' => 'id']));
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
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
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            useUuid: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_argument()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->createMockMigration('animal', $properties);
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--migration' => 'create_animals_table'])
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_argument_using_all_blueprint_column_types()
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
            'soft_deletes_tz_field' => 'softDeletesTz',
            'soft_deletes_field' => 'softDeletes',
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
            ':primary' => ['bigIncrements', 'id'],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $name) => $this->columnTypeToBuiltInType($type)." $name"));

        $this->createMockMigration('animal', $properties, $options);
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--migration' => 'create_animals_table', '--reactor' => 0])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_argument_ignoring_foreign_keys()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $options = [
            ':injects' => [
                ['foreign' => 'dummyId', 'references' => 'id', 'on' => 'dummy', 'onDelete' => 'cascade'],
            ],
        ];

        $expectedPrintedProperties = array_values(Arr::map($properties, fn ($type, $name) => $this->columnTypeToBuiltInType($type)." $name"));

        $this->createMockMigration('animal', $properties, $options);
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--migration' => 'create_animals_table', '--reactor' => 0])
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_with_update_migration()
    {
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_with_non_existing_migration_argument()
    {
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_not_specified_domain()
    {
        $domain = 'Animal';
        $domainExpected = Str::ucfirst($domain);

        $this->artisan('make:event-sourcing-domain')
            ->expectsQuestion('Which domain you want to create?', $domain)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', false)
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domainExpected],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domainExpected.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domainExpected);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_lowercase_domain()
    {
        $domain = 'animal';
        $domainExpected = Str::ucfirst($domain);

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', false)
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domainExpected],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domainExpected.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domainExpected);
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_without_spatie_event_sourcing_installed()
    {
        $this->hideSpatiePackage();

        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain, '--verbose' => 1])
            ->expectsOutputToContain('ERROR  Spatie Event Sourcing package has not been installed. Run what follows:')
            ->expectsOutputToContain('ERROR  composer require spatie/laravel-event-sourcing')
            ->assertFailed();

        $this->assertFalse(
            File::exists(app_path('Domain/'.$domain.'.php'))
        );
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_if_already_exists()
    {
        $domain = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path('Domain/'.$domain));
        File::put(app_path('Domain/'.$domain.'/AggregateRoot.php'), "<?php\necho 'Hello world';\n");

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsOutputToContain('ERROR  Domain already exists.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_with_reserved_name()
    {
        $domain = 'Array';

        $this->artisan('make:event-sourcing-domain', ['name' => $domain])
            ->expectsOutputToContain('The name "'.$domain.'" is reserved by PHP.')
            ->assertFailed();
    }
}
