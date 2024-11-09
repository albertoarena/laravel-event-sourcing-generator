<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\Concerns\WithMockPackages;
use Tests\TestCase;

class MakeEventSourcingDomainCommandBasicTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

    #[RunInSeparateProcess]
    #[Test]
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
        $this->assertMatchesRegularExpression('/\s*--notifications\[=NOTIFICATIONS]\s*Notifications, comma separated \(accepts mail,no,slack,teams\) \[default: "no"]/', $output);
        $this->assertMatchesRegularExpression('/\s*--failed-events\[=FAILED-EVENTS]\s*Indicate if failed events must be created \(accepts 0 or 1\) \[default: "0"]/', $output);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
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
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
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
            'number_of_bones' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
            ->expectsQuestion('Do you want to import properties from existing database migration?', false)
            // Properties
            ->expectsQuestion('Do you want to specify model properties?', true)
            ->expectsQuestion('Property name? (exit to quit)', 'name')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'string')
            ->expectsQuestion('Property name? (exit to quit)', 'age')
            ->expectsQuestion('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)', 'int')
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
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
            'number_of_bones' => 'int',
        ];
        $properties2 = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
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
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
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
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties1, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
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
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
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
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties2, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
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
                    ['Create failed events', 'no'],
                    ['Model properties', 'none'],
                    ['Notifications', 'no'],
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
        $namespace = 'domains';
        $expectedNamespace = 'Domains';
        $domain = 'Animal';
        $model = 'Tiger';

        $properties = [
            'name' => 'string',
            'age' => 'int',
        ];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '-d' => $domain, '--namespace' => $namespace])
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
            'number_of_bones' => 'int',
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
            ->expectsQuestion('Property name? (exit to quit)', 'number_of_bones')
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

        $this->assertDomainGenerated($model, modelProperties: $properties, indentation: 2);
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
                    ['Notifications', 'no'],
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
                    ['Notifications', 'no'],
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
                    ['Notifications', 'no'],
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
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(model: $model, domain: $domain);
    }
}
