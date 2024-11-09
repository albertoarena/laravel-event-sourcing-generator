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

class MakeEventSourcingDomainCommandNotificationsTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_mail_notifications()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['mail'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications)])
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
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_teams_notifications()
    {
        $this->mockMicrosoftTeamsPackage();

        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['teams'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications)])
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
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('WARN  Please install Microsoft Teams notifications via composer:')
            ->doesntExpectOutputToContain('WARN  composer require laravel-notification-channels/microsoft-teams')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_teams_notifications_without_microsoft_teams_package()
    {
        $this->hideMicrosoftTeamsPackage();

        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['teams'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications)])
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
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->expectsOutputToContain('WARN  Please install Microsoft Teams notifications via composer:')
            ->expectsOutputToContain('WARN  composer require laravel-notification-channels/microsoft-teams')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_slack_notifications()
    {
        $this->mockSlackPackage();

        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['slack'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications)])
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
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('WARN  Please install Slack notifications via composer:')
            ->doesntExpectOutputToContain('WARN  composer require laravel/slack-notification-channel')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_slack_notifications_without_slack_package()
    {
        $this->hideSlackPackage();

        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['slack'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications)])
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
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->expectsOutputToContain('WARN  Please install Slack notifications via composer:')
            ->expectsOutputToContain('WARN  composer require laravel/slack-notification-channel')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_all_notifications_and_unit_tests()
    {
        $model = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['mail', 'slack', 'teams'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--notifications' => implode(',', $notifications), '--unit-test' => true])
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
                    ['Create unit test', 'yes'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$model.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated($model, modelProperties: $properties, createUnitTest: true, notifications: $notifications);
    }

    #[RunInSeparateProcess]
    #[Test]
    public function it_can_create_a_model_and_domain_with_all_notifications_and_unit_tests_with_different_domain()
    {
        $model = 'Tiger';
        $domain = 'Animal';

        $properties = [
            'name' => 'string',
            'age' => 'int',
            'number_of_bones' => 'int',
        ];

        $notifications = ['mail', 'slack', 'teams'];

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain, '--notifications' => implode(',', $notifications), '--unit-test' => true])
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
                    ['Create unit test', 'yes'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', implode(',', $notifications)],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'yes')
            // Result
            ->expectsOutputToContain('INFO  Domain ['.$domain.'] with model ['.$model.'] created successfully.')
            ->doesntExpectOutputToContain('A file already exists (it was not overwritten)')
            ->assertSuccessful();

        $this->assertDomainGenerated(
            $model,
            domain: $domain,
            modelProperties: $properties,
            createUnitTest: true,
            notifications: $notifications
        );
    }
}
