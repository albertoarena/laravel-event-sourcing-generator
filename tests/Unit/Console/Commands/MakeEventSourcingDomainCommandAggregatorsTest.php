<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\Concerns\WithMockPackages;
use Tests\TestCase;

class MakeEventSourcingDomainCommandAggregatorsTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

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
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, createAggregateRoot: false, modelProperties: $properties);
    }
}
