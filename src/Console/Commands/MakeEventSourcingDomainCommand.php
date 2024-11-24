<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Console\Commands;

use Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Concerns\HasBlueprintColumnType;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Concerns\CanCreateDirectories;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts\AcceptedNotificationInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Contracts\DefaultSettingsInterface;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Command\Models\CommandSettings;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Migrations\Migration;
use Albertoarena\LaravelEventSourcingGenerator\Domain\PhpParser\Models\MigrationCreateProperty;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Models\StubCallback;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\StubReplacer;
use Albertoarena\LaravelEventSourcingGenerator\Domain\Stubs\Stubs;
use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MakeEventSourcingDomainCommand extends GeneratorCommand
{
    use CanCreateDirectories;
    use HasBlueprintColumnType;

    protected CommandSettings $settings;

    protected StubReplacer $stubReplacer;

    /**
     * @var string
     */
    protected $signature = 'make:event-sourcing-domain 
                            {model : The name of the model}
                            {--d|domain= : The name of the domain}
                            {--namespace=Domain : The namespace or root folder}
                            {--m|migration= : Indicate any existing migration for the model, with or without timestamp prefix}
                            {--a|aggregate-root= : Indicate if aggregate root must be created or not (accepts 0 or 1)}
                            {--r|reactor= : Indicate if reactor must be created or not (accepts 0 or 1)}
                            {--u|unit-test : Indicate if PHPUnit tests must be created}
                            {--p|primary-key= : Indicate which is the primary key (uuid, id)}
                            {--i|indentation=4 : Indentation spaces}
                            {--failed-events=0 : Indicate if failed events must be created (accepts 0 or 1)}
                            {--notifications=no : Indicate if notifications must be created, comma separated (accepts mail,no,slack,teams)}
                            {--root=app : The name of the root folder}';

    /**
     * @var string
     */
    protected $description = 'Create a new domain for Spatie event sourcing';

    /**
     * @var string
     */
    protected $type = 'Domain';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'model' => 'Which is the name of the model?',
        ];
    }

    protected function getDatabaseMigrations(): array
    {
        return Arr::map(
            File::files($this->laravel->basePath('database/migrations')),
            fn (SplFileInfo $path) => $path->getRelativePathname()
        );
    }

    // @codeCoverageIgnoreStart
    protected function getStub(): string
    {
        // This method is unused
        return '';
    }
    // @codeCoverageIgnoreEnd

    protected function qualifyDomain(string $name): string
    {
        $name = ltrim($name, '\\/');

        return str_replace('/', '\\', $name);
    }

    protected function getDomainPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.$this->settings->namespace.'/'.str_replace('\\', '/', $name).'/';
    }

    protected function getNamespacePath(): string
    {
        return $this->laravel['path'].'/'.$this->settings->namespace.'/';
    }

    protected function getTestDomainPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel->basePath('tests/Unit/').$this->settings->namespace.'/'.str_replace('\\', '/', $name).'/';
    }

    protected function alreadyExistsModel(): bool
    {
        return $this->files->exists($this->getDomainPath($this->settings->model).'Actions/Create'.$this->settings->model.'.php');
    }

    /**
     * @throws FileNotFoundException
     */
    protected function stubToClass(
        string $stubPath,
        string $outputPath
    ): void {
        if ($this->files->exists($outputPath)) {
            $basePath = $this->laravel->basePath('app/');
            $outputPath = Str::replaceFirst($basePath, '', $outputPath);
            $this->components->warn('A file already exists (it was not overwritten): '.$outputPath);

            return;
        }

        $stub = $this->files->get($stubPath);

        $this->stubReplacer
            ->queue([
                fn ($stub) => $this->replaceNamespace($stub, $this->settings->domain)
                    ->replaceClass($stub, $this->settings->model),
            ])
            ->run($stub);

        $this->files->put($outputPath, $stub);
    }

    /**
     * @throws Exception
     */
    protected function loadProperties(): void
    {
        // Determine which is the model primary key and set properties
        if ($this->settings->migration) {
            try {
                // Load migration
                $migration = (new Migration($this->settings->migration));
                foreach ($migration->properties() as $property) {
                    $this->settings->modelProperties->add($property);
                    if ($property->type->type === 'Carbon' && $property->name !== 'timestamps') {
                        $this->settings->useCarbon = true;
                    }
                    if ($property->type->warning) {
                        $this->components->warn($property->type->warning);
                    }
                }

                $this->settings->useUuid = $migration->primary() === 'uuid';

                foreach ($migration->ignored() as $ignored) {
                    $this->settings->ignoredProperties->add($ignored);
                    $this->components->warn('Type '.$ignored->type->type.' is not supported for column '.$ignored->name);
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            if ($this->confirm('Do you want to specify model properties?')) {
                while (true) {
                    $name = $this->ask('Property name? (exit to quit)');
                    if ($name === 'exit') {
                        break;
                    }
                    $type = $this->ask('Property type? (e.g. string, int, boolean. Nullable is accepted, e.g. ?string)');
                    $this->settings->modelProperties->add(new MigrationCreateProperty(
                        name: $name,
                        type: $type,
                    ));
                }
            }

            if (is_null($this->settings->useUuid)) {
                $this->settings->useUuid = $this->confirm('Do you want to use uuid as model primary key?', true);
            }
        }
    }

    protected function checkSpatieEventSourcing(): bool
    {
        return class_exists('Spatie\EventSourcing\EventSourcingServiceProvider');
    }

    protected function checkNotificationMicrosoftTeamsPackage(): bool
    {
        return class_exists('NotificationChannels\MicrosoftTeams\MicrosoftTeamsChannel');
    }

    protected function checkNotificationSlackPackage(): bool
    {
        return class_exists('Illuminate\Notifications\Slack\SlackMessage');
    }

    protected function checkPhpunit(): bool
    {
        return class_exists('PHPUnit\Framework\TestCase');
    }

    protected function getModelInput(): string
    {
        return Str::ucfirst(trim($this->argument('model')));
    }

    protected function getDomainInput(string $model): string
    {
        $domain = ! is_null($this->option('domain')) ? Str::ucfirst($this->option('domain')) : null;
        if (! $domain) {
            $domain = $this->ask('Which is the name of the domain?', $model);
        }

        return Str::ucfirst($domain);
    }

    protected function getNotifications(): array
    {
        $notifications = ! is_null($this->option('notifications')) ? explode(',', $this->option('notifications')) : [];

        return array_filter(
            Arr::map($notifications, fn ($item) => Str::trim($item)),
            fn ($item) => in_array($item, AcceptedNotificationInterface::ACCEPTED)
        );
    }

    /**
     * @throws Exception
     */
    protected function bootstrap(): bool
    {
        $primaryKey = ! is_null($this->option('primary-key')) ? $this->option('primary-key') : null;
        if ($primaryKey && ! in_array($primaryKey, ['uuid', 'id'])) {
            $this->components->error('The primary key "'.$primaryKey.'" is not valid (please specify uuid or id)');

            return false;
        }

        $model = $this->getModelInput();
        $this->settings = new CommandSettings(
            model: $model,
            domain: $this->getDomainInput($model),
            namespace: Str::ucfirst($this->option('namespace')),
            migration: $this->option('migration'),
            createAggregateRoot: ! is_null($this->option('aggregate-root')) ? (bool) $this->option('aggregate-root') : null,
            createReactor: ! is_null($this->option('reactor')) ? (bool) $this->option('reactor') : null,
            indentation: (int) $this->option('indentation'),
            notifications: $this->getNotifications(),
            rootFolder: ! is_null($this->option('root')) ? $this->option('root') : DefaultSettingsInterface::APP,
            useUuid: ! is_null($primaryKey) ? $primaryKey === 'uuid' : null,
            createUnitTest: (bool) $this->option('unit-test'),
            createFailedEvents: (bool) $this->option('failed-events'),
        );

        // Set root folder
        if ($this->settings->rootFolder !== DefaultSettingsInterface::APP) {
            $this->laravel['path'] = Str::replaceLast('/app', '/'.$this->settings->rootFolder, $this->laravel['path']);
        }

        // Check if Spatie event-sourcing package has been installed
        if (! $this->checkSpatieEventSourcing()) {
            $this->components->error('Spatie Event Sourcing package has not been installed. Run what follows:');
            $this->components->error('composer require spatie/laravel-event-sourcing');

            return false;
        }

        // Check if the given name is a reserved word within PHP language
        if ($this->isReservedName($this->settings->model)) {
            $this->components->error('The model "'.$this->settings->model.'" is reserved by PHP.');

            return false;
        }

        // Check if the given domain is a reserved word within PHP language
        if ($this->isReservedName($this->settings->domain)) {
            $this->components->error('The domain "'.$this->settings->domain.'" is reserved by PHP.');

            return false;
        }

        // Check if the given namespace is a reserved word within PHP language
        if ($this->isReservedName($this->settings->namespace)) {
            $this->components->error('The namespace "'.$this->settings->namespace.'" is reserved by PHP.');

            return false;
        }

        // Check if the domain already exists
        if ($this->alreadyExistsModel()) {
            $this->components->error('Model already exists.');

            return false;
        }

        // Check if phpunit has been installed
        if ($this->settings->createUnitTest && ! $this->checkPhpunit()) {
            $this->components->warn('PHPUnit package has not been installed. Run what follows:');
            $this->components->warn('composer require phpunit/phpunit --dev');
        }

        // Get other options
        if (is_null($this->settings->migration)) {
            if ($this->confirm('Do you want to import properties from existing database migration?', true)) {
                $this->settings->migration = $this->choice('Select database migration', $this->getDatabaseMigrations());
            }
        }

        $this->stubReplacer = new StubReplacer($this->settings);

        // Load properties, from migration file or manually
        $this->loadProperties();

        // If not using uuid as primary key, no Aggregate Root
        if ($this->settings->useUuid === false) {
            $this->line('You are not using uuid as model primary key, therefore you cannot use an AggregateRoot class');
            $this->settings->createAggregateRoot = false;
        }

        if ($this->settings->useUuid && is_null($this->settings->createAggregateRoot)) {
            $this->settings->createAggregateRoot = $this->confirm('Do you want to create an AggregateRoot class?', true);
        }

        if (is_null($this->settings->createReactor)) {
            $this->settings->createReactor = $this->confirm('Do you want to create a Reactor class?', true);
        }

        $this->settings->nameAsPrefix = Str::lcfirst(Str::camel($this->settings->model));
        $this->settings->domainPath = $this->getDomainPath($this->qualifyDomain($this->settings->domain));
        $this->settings->namespacePath = $this->getNamespacePath();
        $this->settings->testDomainPath = $this->getTestDomainPath($this->qualifyDomain($this->settings->domain));

        return true;
    }

    protected function confirmChoices(): bool
    {
        $modelProperties = $this->settings->modelProperties->withoutReservedFields()->toArray();
        $currentChoices = array_filter([
            ['Model', $this->settings->model],
            ['Domain', $this->settings->domain],
            $this->settings->rootFolder !== DefaultSettingsInterface::APP ? ['Root folder', $this->settings->rootFolder] : false,
            ['Namespace', $this->settings->namespace],
            ['Path', $this->settings->namespace.'/'.$this->settings->domain.'/'.$this->settings->model],
            ['Use migration', basename($this->settings->migration) ?: 'no'],
            ['Primary key', $this->settings->primaryKey()],
            ['Create AggregateRoot class', $this->settings->createAggregateRoot ? 'yes' : 'no'],
            ['Create Reactor class', $this->settings->createReactor ? 'yes' : 'no'],
            ['Create PHPUnit tests', $this->settings->createUnitTest ? 'yes' : 'no'],
            ['Create failed events', $this->settings->createFailedEvents ? 'yes' : 'no'],
            [
                'Model properties',
                $modelProperties ?
                    implode("\n", Arr::map(
                        $modelProperties,
                        function (MigrationCreateProperty $property) {
                            $type = $property->type->toBuiltInType();
                            $nullable = $property->type->nullable ? '?' : '';

                            return "$nullable$type $property->name";
                        })
                    ) :
                    'none',
            ],
            ['Notifications', $this->settings->notifications ? implode(',', $this->settings->notifications) : 'no'],
        ]);

        $this->line('Your choices:');
        $this->table(
            ['Option', 'Choice'],
            $currentChoices
        );

        return $this->confirm('Do you confirm the generation of the domain?', true);
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createDomainFiles(): self
    {
        $stubCallback = new StubCallback(
            function (string $stubPath, string $outputPath) {
                $this->stubToClass($stubPath, $outputPath);
            },
        );

        (new Stubs(
            laravel: $this->laravel,
            settings: $this->settings,
        ))->resolve($stubCallback);

        return $this;
    }

    protected function outputResult(): void
    {
        $this->components->info(sprintf('%s [%s] with model [%s] created successfully.', $this->type, $this->settings->domain, $this->settings->model));
    }

    // @phpstan-ignore method.childReturnType
    public function handle(): int
    {
        try {
            if (! $this->bootstrap()) {
                return self::FAILURE;
            }

            if (! $this->confirmChoices()) {
                $this->components->warn('Aborted!');

                return self::FAILURE;
            }

            $this->createDirectories()
                ->createDomainFiles()
                ->outputResult();

            if (in_array('teams', $this->settings->notifications) && ! $this->checkNotificationMicrosoftTeamsPackage()) {
                $this->components->warn('Please install Microsoft Teams notifications via composer:');
                $this->components->warn('composer require laravel-notification-channels/microsoft-teams');
            }

            if (in_array('slack', $this->settings->notifications) && ! $this->checkNotificationSlackPackage()) {
                $this->components->warn('Please install Slack notifications via composer:');
                $this->components->warn('composer require laravel/slack-notification-channel');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->components->error('There was an error: '.$e->getMessage().'.');

            // Log error
            Log::error('make:event-sourcing-domain failed', [
                'arguments' => $this->input->getArguments(),
                'options' => $this->input->getOptions(),
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
