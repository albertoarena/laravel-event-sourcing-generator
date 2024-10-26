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
        $this->assertMatchesRegularExpression("/Arguments:\n\s*name\s*Name of the model/", $output);
        $this->assertMatchesRegularExpression("/\s*-d, --domain\[=DOMAIN]\s*Domain \[default: same as model]/", $output);
        $this->assertMatchesRegularExpression("/\s*--namespace\[=NAMESPACE]\s*Namespace \[default: \"Domain\"]/", $output);
        $this->assertMatchesRegularExpression("/\s*-m, --migration\[=MIGRATION]\s*Existing migration for the model, with or without timestamp prefix/", $output);
        $this->assertMatchesRegularExpression("/\s*-a, --aggregate_root\[=AGGREGATE_ROOT]\s*Create aggregate root/", $output);
        $this->assertMatchesRegularExpression("/\s*-r, --reactor\[=REACTOR]\s*Create reactor/", $output);
        $this->assertMatchesRegularExpression("/\s*-i, --indentation\[=INDENTATION]\s*Indentation spaces \[default: \"4\"]/", $output);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_domain()
    {
        $name = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            name: $name,
            domain: $domain,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_multiple_models_for_same_domain()
    {
        $name1 = 'Tiger';
        $name2 = 'Lion';
        $domain = 'Animal';

        $properties1 = [
            'name' => 'string',
            'age' => 'int',
        ];
        $properties2 = [
            'name' => 'string',
            'age' => 'int',
            'colour' => '?string',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name1, '--domain' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name1],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$name1],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties1, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name1.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            name: $name1,
            domain: $domain,
            modelProperties: $properties1
        );

        $this->artisan('make:event-sourcing-domain', ['name' => $name2, '--domain' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'colour')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', '?string')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', false)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name2],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$name2],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties2, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name2.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            name: $name2,
            domain: $domain,
            createAggregateRoot: false,
            useUuid: false,
            modelProperties: $properties2
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_nullable_parameters()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => '?int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', '?int')
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_using_id_as_primary_key()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', false)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, useUuid: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_properties()
    {
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_namespace()
    {
        $namespace = 'Domains';
        $domain = 'Animal';
        $name = 'Tiger';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '-d' => $domain, '--namespace' => $namespace])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $domain],
                    ['Namespace', $namespace],
                    ['Path', $namespace.'/'.$domain.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            name: $name,
            domain: $domain,
            namespace: $namespace,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_lowercase_namespace()
    {
        $namespace = 'domains';
        $expectedNamespace = 'Domains';
        $domain = 'Animal';
        $name = 'Tiger';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '-d' => $domain, '--namespace' => $namespace])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $domain],
                    ['Namespace', $expectedNamespace],
                    ['Path', $expectedNamespace.'/'.$domain.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            name: $name,
            domain: $domain,
            namespace: $expectedNamespace,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_indentation_argument()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--indentation' => 2])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, modelProperties: $properties, indentation: 2);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_aggregate_root_argument()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--aggregate_root' => true])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_aggregate_root()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, createAggregateRoot: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_reactor_argument()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--reactor' => true])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to use uuid as model primary key?', true)
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_reactor()
    {
        $name = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($name, createReactor: false, modelProperties: $properties);
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_using_uuid_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $migrationPath = basename($this->createMockCreateMigration('animal', $properties));
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $name,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_using_id_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $migrationPath = basename($this->createMockCreateMigration('animal', $properties, [':primary' => 'id']));
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $name)
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', $migrationPath],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $name,
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
    public function it_can_create_a_model_and_domain_with_migration_argument()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->createMockCreateMigration('animal', $properties);
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--migration' => 'create_animals_table'])
            ->expectsQuestion('Which is the domain name?', $name)
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
            ->expectsQuestion('Do you want to create a Reactor class?', true)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $name) => "$type $name"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $name,
            migration: 'create_animals_table',
            modelProperties: $properties
        );
    }

    /**
     * @throws Exception
     */
    #[RunInSeparateProcess]
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

        $this->createMockCreateMigration('animal', $properties, $options);
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--migration' => 'create_animals_table', '--reactor' => 0])
            ->expectsQuestion('Which is the domain name?', $name)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $name,
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
    public function it_can_create_a_model_and_domain_with_migration_argument_ignoring_foreign_keys()
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

        $this->createMockCreateMigration('animal', $properties, $options);
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--migration' => 'create_animals_table', '--reactor' => 0])
            ->expectsQuestion('Which is the domain name?', $name)
            // Confirmation
            ->expectsOutput('Your choices:')
            ->expectsTable(
                ['Option', 'Choice'],
                [
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'create_animals_table'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $name,
            migration: 'create_animals_table',
            createAggregateRoot: false,
            createReactor: false,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_not_specified_domain()
    {
        $name = 'Animal';
        $nameExpected = Str::ucfirst($name);

        $this->artisan('make:event-sourcing-domain')
            ->expectsQuestion('Which model you want to create?', $name)
            ->expectsQuestion('Which is the domain name?', $name)
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
                    ['Model', $nameExpected],
                    ['Domain', $nameExpected],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$nameExpected.'/'.$nameExpected],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$nameExpected.'] with model ['.$nameExpected.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($nameExpected);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_lowercase_inputs()
    {
        $name = 'tiger';
        $domain = 'animal';
        $nameExpected = Str::ucfirst($name);
        $domainExpected = Str::ucfirst($domain);

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $domain)
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
                    ['Model', $nameExpected],
                    ['Domain', $domainExpected],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domainExpected.'/'.$nameExpected],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domainExpected.'] with model ['.$nameExpected.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(name: $nameExpected, domain: $domainExpected);
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_partially_create_a_model_without_overwriting_already_existing_files()
    {
        $name = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$name"));
        File::makeDirectory(app_path("Domain/$name/Events"));
        File::makeDirectory(app_path("Domain/$name/Reactors"));
        File::put(app_path("Domain/$name/Events/{$name}Deleted.php"), "<?php\nclass {$name}Deleted {}\n");
        File::put(app_path("Domain/$name/Reactors/{$name}Reactor.php"), "<?php\nclass {$name}Reactor {}\n");

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $name])
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
                    ['Model', $name],
                    ['Domain', $name],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$name.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            ->expectsOutputToContain('WARN  A file already exists (it was not overwritten): '."Domain/$name/Events/{$name}Deleted.php")
            ->expectsOutputToContain('WARN  A file already exists (it was not overwritten): '."Domain/$name/Reactors/{$name}Reactor.php")
            ->expectsOutputToContain('INFO  Domain ['.$name.'] with model ['.$name.'] created successfully.')
            ->assertSuccessful();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_create_a_model_if_only_the_domain_already_exists()
    {
        $name = 'Tiger';
        $domain = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$domain"));
        File::makeDirectory(app_path("Domain/$domain/Actions"));
        File::put(app_path("Domain/$domain/Actions/CreateLion.php"), "<?php\nclass CreateLion {}\n");

        $this->artisan('make:event-sourcing-domain', ['name' => $name])
            ->expectsQuestion('Which is the domain name?', $domain)
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
                    ['Model', $name],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$name],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$name.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(name: $name, domain: $domain);
    }

    #[Test]
    public function it_cannot_create_a_model_and_domain_with_update_migration()
    {
        $name = 'Animal';
        $properties = [
            'new_field' => '?string',
        ];
        $migrationPath = $this->createMockUpdateMigration('animal', $properties);

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $name, '--migration' => $migrationPath])
            ->expectsOutputToContain('ERROR  There was an error: Update migration file is not supported.')
            ->assertFailed();
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_cannot_create_a_model_and_domain_with_non_existing_migration()
    {
        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $name, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_and_domain_without_spatie_event_sourcing_installed()
    {
        $this->hideSpatiePackage();

        $name = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $name, '--verbose' => 1])
            ->expectsOutputToContain('ERROR  Spatie Event Sourcing package has not been installed. Run what follows:')
            ->expectsOutputToContain('ERROR  composer require spatie/laravel-event-sourcing')
            ->assertFailed();

        $this->assertFalse(
            File::exists(app_path('Domain/'.$name.'.php'))
        );
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_if_already_exists()
    {
        $name = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$name"));
        File::makeDirectory(app_path("Domain/$name/Actions"));
        File::put(app_path("Domain/$name/Actions/Create$name.php"), "<?php\nclass CreateAnimal {}\n");

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $name])
            ->expectsOutputToContain('ERROR  Model already exists.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_with_reserved_name()
    {
        $name = 'Array';
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $domain])
            ->expectsOutputToContain('The name "'.$name.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_with_reserved_name()
    {
        $name = 'Animal';
        $domain = 'Array';

        $this->artisan('make:event-sourcing-domain', ['name' => $name, '--domain' => $domain])
            ->expectsOutputToContain('The domain "'.$domain.'" is reserved by PHP.')
            ->assertFailed();
    }
}
