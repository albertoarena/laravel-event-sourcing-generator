<?php

namespace Tests\Unit\Console\Commands;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\TestCase;

class DomainMakerCommandTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;

    protected function mockSpatiePackage(): void
    {
        // Create mocked Spatie event sourcing provider, used by command to determine if Spatie package has been installed or not
        Mockery::mock('Spatie\EventSourcing\EventSourcingServiceProvider')->makePartial();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_show_help_for_artisan_command()
    {
        $this->withoutMockingConsoleOutput()
            ->artisan('help make:event-domain');

        $output = Artisan::output();
        $this->assertMatchesRegularExpression("/Description:\n\s*Create a new domain for Spatie event sourcing/", $output);
        $this->assertMatchesRegularExpression("/Usage:\n\s*make:event-domain \[options] \[--] <name>/", $output);
        $this->assertMatchesRegularExpression("/Arguments:\n\s*name\s*Name of domain/", $output);
        $this->assertMatchesRegularExpression("/\s*-d, --domain\[=DOMAIN]\s*Domain base root \[default: \"Domain\"]/", $output);
        $this->assertMatchesRegularExpression("/\s*-m, --migration\[=MIGRATION]\s*Existing migration for the model, with or without timestamp prefix/", $output);
        $this->assertMatchesRegularExpression("/\s*--aggregate_root\[=AGGREGATE_ROOT]\s*Create aggregate root/", $output);
        $this->assertMatchesRegularExpression("/\s*--indentation\[=INDENTATION]\s*Indentation spaces \[default: \"4\"]/", $output);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command()
    {
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
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
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', false)
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
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
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', false)
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
        $this->mockSpatiePackage();

        $rootDomain = 'Domains';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-domain', ['name' => $domain, '-d' => $rootDomain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
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
    public function it_can_create_a_domain_via_artisan_command_with_aggregate_root_argument()
    {
        $this->mockSpatiePackage();

        $domain = 'Hello';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this
            ->artisan('make:event-domain', ['name' => $domain, '--aggregate_root' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
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
    public function it_can_create_a_domain_via_artisan_command_with_indentation_argument()
    {
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-domain', ['name' => $domain, '--indentation' => 2])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
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
    public function it_can_create_a_domain_via_artisan_command_without_aggregate_root()
    {
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name (exit to quit)?', 'name')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'string')
            ->expectsQuestion('Property name (exit to quit)?', 'age')
            ->expectsQuestion('Property type (e.g. string, int, boolean)?', 'int')
            ->expectsQuestion('Property name (exit to quit)?', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', false)
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated($domain, createAggregateRoot: false, modelProperties: $properties);
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_from_existing_migration_using_uuid_as_primary_key()
    {
        $this->mockSpatiePackage();
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $migrationPath = $this->createMockMigration('animal', $properties, 'uuid');
        $domain = 'Animal';

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', true)
            ->expectsChoice(
                'Select database migration',
                $migrationPath,
                [$migrationPath],
            )
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
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
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animal_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_migration_argument()
    {
        $this->mockSpatiePackage();
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->createMockMigration('animal', $properties, 'uuid');
        $domain = 'Animal';

        $this->artisan('make:event-domain', ['name' => $domain, '--migration' => 'create_animal_table'])
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Domain', $domain],
                    ['Root domain folder', 'Domain'],
                    ['Use migration', 'create_animal_table'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', implode(',', Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] created successfully.')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $domain,
            migration: 'create_animal_table',
            modelProperties: $properties
        );
    }

    #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_with_non_existing_migration_argument()
    {
        $this->mockSpatiePackage();

        $domain = 'Animal';

        $this->artisan('make:event-domain', ['name' => $domain, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_domain_via_artisan_command_with_lowercase_domain()
    {
        $this->mockSpatiePackage();

        $domain = 'hello';
        $domainExpected = Str::ucfirst($domain);

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', false)
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
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
        $domain = 'Animal';

        $this->artisan('make:event-domain', ['name' => $domain, '--verbose' => 1])
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
        $this->mockSpatiePackage();

        $domain = 'Hello';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path('Domain/'.$domain));
        File::put(app_path('Domain/'.$domain.'/AggregateRoot.php'), "<?php\necho 'Hello world';\n");

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsOutputToContain('ERROR  Domain already exists.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_via_artisan_command_with_reserved_name()
    {
        $this->mockSpatiePackage();

        $domain = 'Array';

        $this->artisan('make:event-domain', ['name' => $domain])
            ->expectsOutputToContain('The name "'.$domain.'" is reserved by PHP.')
            ->assertFailed();
    }
}
