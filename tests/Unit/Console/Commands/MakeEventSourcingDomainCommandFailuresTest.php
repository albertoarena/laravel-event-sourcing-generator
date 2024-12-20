<?php

namespace Tests\Unit\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\AssertsDomainGenerated;
use Tests\Concerns\CreatesMockMigration;
use Tests\Concerns\WithMockPackages;
use Tests\TestCase;

class MakeEventSourcingDomainCommandFailuresTest extends TestCase
{
    use AssertsDomainGenerated;
    use CreatesMockMigration;
    use HasBlueprintColumnType;
    use WithMockPackages;

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
                    ['Use migration', 'no'],
                    ['Primary key', 'uuid'],
                    ['Create Aggregate class', 'yes'],
                    ['Create Reactor class', 'yes'],
                    ['Create PHPUnit tests', 'no'],
                    ['Create failed events', 'no'],
                    ['Model properties', implode("\n", Arr::map($properties, fn ($type, $model) => "$type $model"))],
                    ['Notifications', 'no'],
                ]
            )
            ->expectsConfirmation('Do you confirm the generation of the domain?', 'no')
            // Result
            ->expectsOutputToContain('WARN  Aborted!')
            ->assertFailed();
    }

    #[RunInSeparateProcess]
    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_cannot_create_a_model_with_reserved_name()
    {
        $model = 'Array';
        $domain = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
            ->expectsOutputToContain('The model "'.$model.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[Test]
    public function it_cannot_create_a_domain_with_reserved_name()
    {
        $model = 'Animal';
        $domain = 'Array';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $domain])
            ->expectsOutputToContain('The domain "'.$domain.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[Test]
    public function it_cannot_create_a_domain_with_reserved_namespace()
    {
        $model = 'Animal';
        $namespace = 'Array';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--domain' => $model, '--namespace' => $namespace])
            ->expectsOutputToContain('The namespace "'.$namespace.'" is reserved by PHP.')
            ->assertFailed();
    }

    #[Test]
    public function it_cannot_create_a_domain_with_invalid_primary_key()
    {
        $model = 'Animal';

        $this->artisan('make:event-sourcing-domain', ['model' => $model, '--primary-key' => 'hello'])
            ->expectsOutputToContain('The primary key "hello" is not valid (please specify uuid or id)')
            ->assertFailed();
    }
}
