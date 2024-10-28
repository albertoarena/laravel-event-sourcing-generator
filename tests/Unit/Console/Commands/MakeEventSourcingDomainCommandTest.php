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
        $this->assertMatchesRegularExpression("/Usage:\n\s*make:event-sourcing-domain \[options] \[--] <model>/", $output);
        $this->assertMatchesRegularExpression("/Arguments:\n\s*model\s*The name of the model/", $output);
        $this->assertMatchesRegularExpression('/\s*-d, --domain\[=DOMAIN]\s*The name of the domain/', $output);
        $this->assertMatchesRegularExpression('/\s*--namespace\[=NAMESPACE]\s*The namespace or root folder \[default: "Domain"]/', $output);
        $this->assertMatchesRegularExpression('/\s*-m, --migration\[=MIGRATION]\s*Indicate any existing migration for the model, with or without timestamp prefix/', $output);
        $this->assertMatchesRegularExpression('/\s*-a, --aggregate-root\[=AGGREGATE-ROOT]\s*Indicate if aggregate root must be created or not \(accepts 0 or 1\)/', $output);
        $this->assertMatchesRegularExpression('/\s*-r, --reactor\[=REACTOR]\s*Indicate if reactor must be created or not \(accepts 0 or 1\)/', $output);
        $this->assertMatchesRegularExpression('/\s*-u, --unit-test\s*Indicate if unit test must be created/', $output);
        $this->assertMatchesRegularExpression('/\s*-p, --primary-key\[=PRIMARY-KEY]\s*Indicate which is the primary key \(uuid, id\)/', $output);
        $this->assertMatchesRegularExpression('/\s*-i, --indentation\[=INDENTATION]\s*Indentation spaces \[default: "4"]/', $output);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_domain()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
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
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            model: $model,
            domain: $domain,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_multiple_models_for_same_domain()
    {
        $model1 = 'Tiger';
        $model2 = 'Lion';
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

        $this->artisan('make:event-sourcing-domain', ['model' => $model1, '--domain' => $domain])
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
                    ['Model', $model1],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model1],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties1, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model1.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            model: $model1,
            domain: $domain,
            modelProperties: $properties1
        );

        $this->artisan('make:event-sourcing-domain', ['model' => $model2, '--domain' => $domain])
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
                    ['Model', $model2],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model2],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties2, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model2.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            model: $model2,
            domain: $domain,
            createAggregateRoot: false,
            useUuid: false,
            modelProperties: $properties2
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_nullable_properties()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => '?int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_uuid_as_primary_key_argument()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--primary-key' => 'uuid'])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
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
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_using_id_as_primary_key()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, useUuid: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_id_as_primary_key_argument()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--primary-key' => 'id'])
            ->expectsQuestion('Which is the name of the domain?', $model)
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
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
                    ['Use migration', 'no'],
                    ['Primary key', 'id'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, useUuid: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_properties()
    {
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_namespace()
    {
        $modelspace = 'Domains';
        $domain = 'Animal';
        $model = 'Tiger';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '-d' => $domain, '--namespace' => $modelspace])
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
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', $modelspace],
                    ['Path', $modelspace.'/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            model: $model,
            domain: $domain,
            namespace: $modelspace,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_different_lowercase_namespace()
    {
        $modelspace = 'domains';
        $expectedNamespace = 'Domains';
        $domain = 'Animal';
        $model = 'Tiger';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '-d' => $domain, '--namespace' => $modelspace])
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
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', $expectedNamespace],
                    ['Path', $expectedNamespace.'/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            model: $model,
            domain: $domain,
            namespace: $expectedNamespace,
            modelProperties: $properties
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_indentation_argument()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--indentation' => 2])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, indentation: 2);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_aggregate_root_argument()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--aggregate-root' => true])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],

                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_aggregate_root()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, createAggregateRoot: false, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_reactor_argument()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--reactor' => true])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_without_reactor()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'no'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, createReactor: false, modelProperties: $properties);
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
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
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
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_using_id_as_primary_key()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
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
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
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
    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_migration_argument()
    {
        $properties = [
            'name' => 'string',
            'age' => 'int',
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
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
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
    #[RunInSeparateProcess]
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
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
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

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_not_specified_domain()
    {
        $model = 'Animal';
        $modelExpected = Str::ucfirst($model);

        $this->artisan('make:event-sourcing-domain')
            ->expectsQuestion('Which is the name of the model?', $model)
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $modelExpected],
                    ['Domain', $modelExpected],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$modelExpected.'/'.$modelExpected],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$modelExpected.'] with model ['.$modelExpected.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($modelExpected);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_lowercase_inputs()
    {
        $model = 'tiger';
        $domain = 'animal';
        $modelExpected = Str::ucfirst($model);
        $domainExpected = Str::ucfirst($domain);

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $domain)
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
                    ['Model', $modelExpected],
                    ['Domain', $domainExpected],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domainExpected.'/'.$modelExpected],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domainExpected.'] with model ['.$modelExpected.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(model: $modelExpected, domain: $domainExpected);
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_partially_create_a_model_without_overwriting_already_existing_files()
    {
        $model = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$model"));
        File::makeDirectory(app_path("Domain/$model/Events"));
        File::makeDirectory(app_path("Domain/$model/Reactors"));
        File::put(app_path("Domain/$model/Events/{$model}Deleted.php"), "<?php\nclass {$model}Deleted {}\n");
        File::put(app_path("Domain/$model/Reactors/{$model}Reactor.php"), "<?php\nclass {$model}Reactor {}\n");

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model])
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            ->expectsOutputToContain('WARN  A file already exists (it was not overwritten): '."Domain/$model/Events/{$model}Deleted.php")
            ->expectsOutputToContain('WARN  A file already exists (it was not overwritten): '."Domain/$model/Reactors/{$model}Reactor.php")
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->assertSuccessful();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_can_create_a_model_even_if_the_domain_already_exists()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$domain"));
        File::makeDirectory(app_path("Domain/$domain/Actions"));
        File::put(app_path("Domain/$domain/Actions/CreateLion.php"), "<?php\nclass CreateLion {}\n");

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $domain)
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
                    ['Model', $model],
                    ['Domain', $domain],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$domain.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Model properties', 'none'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(model: $model, domain: $domain);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_unit_tests()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--primary-key' => 'uuid', '--unit-test' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'exit')
            // Options
            ->expectsQuestion('Do you want to create an AggregateRoot class?', true)
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
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, domain: $domain, modelProperties: $properties, createUnitTest: true);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_unit_tests_using_id_as_primary_key()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--primary-key' => 'id', '--unit-test' => true])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
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
                    ['Create AggregateRoot class', 'no'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'yes'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
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
    #[RunInSeparateProcess]
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
                    ['Create AggregateRoot class', 'no'],
                    ['Model properties', implode("\n", $expectedPrintedProperties)],
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
            modelProperties: $properties,
            createUnitTest: true
        );
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_cannot_create_a_model_and_domain_if_choices_are_not_confirmed()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model])
            ->expectsQuestion('Which is the name of the domain?', $model)
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
                    ['Model', $model],
                    ['Domain', $model],
                    ['Namespace', 'Domain'],
                    ['Path', 'Domain/'.$model.'/'.$model],
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create AggregateRoot class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create unit test', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'no')
            // Result
            ->expectsOutputToContain('WARN  Aborted!')
            ->assertFailed();
    }

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
    #[Test]
    public function it_cannot_create_a_model_and_domain_with_non_existing_migration()
    {
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--migration' => 'create_non_existing_table'])
            ->expectsOutputToContain('ERROR  There was an error: Migration file does not exist.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_and_domain_without_spatie_event_sourcing_installed()
    {
        $this->hideSpatiePackage();

        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--verbose' => 1])
            ->expectsOutputToContain('ERROR  Spatie Event Sourcing package has not been installed. Run what follows:')
            ->expectsOutputToContain('ERROR  composer require spatie/laravel-event-sourcing')
            ->assertFailed();

        $this->assertFalse(
            File::exists(app_path('Domain/'.$model.'.php'))
        );
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_if_already_exists()
    {
        $model = 'Animal';

        // Create domain structure
        File::makeDirectory(app_path('Domain'));
        File::makeDirectory(app_path("Domain/$model"));
        File::makeDirectory(app_path("Domain/$model/Actions"));
        File::put(app_path("Domain/$model/Actions/Create$model.php"), "<?php\nclass CreateAnimal {}\n");

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model])
            ->expectsOutputToContain('ERROR  Model already exists.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_model_with_reserved_name()
    {
        $model = 'Array';
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
            ->expectsOutputToContain('The model "'.$model.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_with_reserved_name()
    {
        $model = 'Animal';
        $domain = 'Array';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
            ->expectsOutputToContain('The domain "'.$domain.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[RunInSeparateProcess] #[Test]
    public function it_cannot_create_a_domain_with_invalid_primary_key()
    {
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--primary-key' => 'hello'])
            ->expectsOutputToContain('The primary key "hello" is not valid (please specify uuid or id)')
            ->assertFailed();
    }
}
